<?php

declare(strict_types=1);

namespace PHPeek\SystemMetrics\Sources\Process;

use DateTimeImmutable;
use PHPeek\SystemMetrics\Contracts\FileReaderInterface;
use PHPeek\SystemMetrics\Contracts\ProcessMetricsSource;
use PHPeek\SystemMetrics\DTO\Metrics\Process\ProcessGroupSnapshot;
use PHPeek\SystemMetrics\DTO\Result;
use PHPeek\SystemMetrics\Exceptions\SystemMetricsException;
use PHPeek\SystemMetrics\Support\FileReader;
use PHPeek\SystemMetrics\Support\Parser\LinuxProcPidStatParser;

/**
 * Reads process metrics from Linux /proc/{pid}/ filesystem.
 */
final class LinuxProcProcessMetricsSource implements ProcessMetricsSource
{
    public function __construct(
        private readonly FileReaderInterface $fileReader = new FileReader,
        private readonly LinuxProcPidStatParser $parser = new LinuxProcPidStatParser,
    ) {}

    public function read(int $pid): Result
    {
        $result = $this->fileReader->read("/proc/{$pid}/stat");

        if ($result->isFailure()) {
            $error = $result->getError();
            assert($error !== null);

            /** @var Result<\PHPeek\SystemMetrics\DTO\Metrics\Process\ProcessSnapshot> */
            return Result::failure($error);
        }

        return $this->parser->parse($result->getValue(), $pid);
    }

    public function readProcessGroup(int $rootPid): Result
    {
        // Read root process
        $rootResult = $this->read($rootPid);
        if ($rootResult->isFailure()) {
            $error = $rootResult->getError();
            assert($error !== null);

            /** @var Result<\PHPeek\SystemMetrics\DTO\Metrics\Process\ProcessGroupSnapshot> */
            return Result::failure($error);
        }

        $root = $rootResult->getValue();
        $children = [];

        // Find all child PIDs recursively
        $childPids = $this->findChildPids($rootPid);

        // Read each child process (best-effort, skip if process exits)
        foreach ($childPids as $childPid) {
            $childResult = $this->read($childPid);
            if ($childResult->isSuccess()) {
                $children[] = $childResult->getValue();
            }
            // Silently skip failed reads (process may have exited)
        }

        return Result::success(new ProcessGroupSnapshot(
            rootPid: $rootPid,
            root: $root,
            children: $children,
            timestamp: new DateTimeImmutable
        ));
    }

    /**
     * Find all child PIDs recursively.
     *
     * @return int[]
     */
    private function findChildPids(int $parentPid): array
    {
        $children = [];

        // Read /proc directory to find all processes
        $procDirs = @glob('/proc/[0-9]*', GLOB_ONLYDIR);
        if ($procDirs === false) {
            return [];
        }

        foreach ($procDirs as $procDir) {
            $pid = (int) basename($procDir);
            if ($pid === $parentPid) {
                continue;  // Skip parent
            }

            // Read /proc/{pid}/stat to check parent PID
            $statResult = $this->fileReader->read("{$procDir}/stat");
            if ($statResult->isFailure()) {
                continue;  // Process may have exited
            }

            $content = $statResult->getValue();

            // Extract PPID (field 4) from stat file
            $closingParen = strrpos($content, ')');
            if ($closingParen === false) {
                continue;
            }

            $afterName = substr($content, $closingParen + 2);
            $fields = preg_split('/\s+/', $afterName);

            if ($fields === false || count($fields) < 2) {
                continue;
            }

            $ppid = (int) $fields[1];  // Field 4

            if ($ppid === $parentPid) {
                $children[] = $pid;

                // Recursively find grandchildren
                $grandchildren = $this->findChildPids($pid);
                $children = array_merge($children, $grandchildren);
            }
        }

        return $children;
    }
}
