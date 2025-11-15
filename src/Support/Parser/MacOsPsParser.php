<?php

declare(strict_types=1);

namespace PHPeek\SystemMetrics\Support\Parser;

use DateTimeImmutable;
use PHPeek\SystemMetrics\DTO\Metrics\Cpu\CpuTimes;
use PHPeek\SystemMetrics\DTO\Metrics\Process\ProcessResourceUsage;
use PHPeek\SystemMetrics\DTO\Metrics\Process\ProcessSnapshot;
use PHPeek\SystemMetrics\DTO\Result;
use PHPeek\SystemMetrics\Exceptions\ParseException;

/**
 * Parses macOS ps command output for process metrics.
 *
 * Expected format: ps -p {pid} -o pid,ppid,rss,vsz,time
 * Output: PID  PPID    RSS      VSZ      TIME
 *         123  1       1234     5678     00:01:23
 */
final class MacOsPsParser
{
    /**
     * Parse ps command output into ProcessSnapshot.
     *
     * @return Result<ProcessSnapshot>
     */
    public function parse(string $output, int $expectedPid): Result
    {
        $lines = array_filter(
            explode("\n", trim($output)),
            fn (string $line) => $line !== ''
        );

        if (count($lines) < 2) {
            /** @var Result<ProcessSnapshot> */
            return Result::failure(
                ParseException::forCommand('ps', 'Insufficient output lines')
            );
        }

        // Skip header line, parse data line
        $dataLine = trim($lines[1]);
        $fields = preg_split('/\s+/', $dataLine);

        if ($fields === false || count($fields) < 5) {
            /** @var Result<ProcessSnapshot> */
            return Result::failure(
                ParseException::forCommand('ps', 'Invalid format: insufficient fields')
            );
        }

        $pid = (int) $fields[0];
        $ppid = (int) $fields[1];
        $rss = (int) $fields[2]; // In kilobytes
        $vsz = (int) $fields[3]; // In kilobytes
        $timeStr = $fields[4]; // Format: HH:MM:SS or MM:SS.CC

        // Convert RSS and VSZ from kilobytes to bytes
        $rssBytes = $rss * 1024;
        $vszBytes = $vsz * 1024;

        // Parse time string into seconds
        $cpuSeconds = $this->parseTimeString($timeStr);

        // Convert seconds to ticks (USER_HZ = 100)
        $cpuTicks = (int) ($cpuSeconds * 100);

        // ps combines user + system time, so we put it all in user
        $cpuTimes = new CpuTimes(
            user: $cpuTicks,
            nice: 0,
            system: 0,
            idle: 0,
            iowait: 0,
            irq: 0,
            softirq: 0,
            steal: 0
        );

        $resources = new ProcessResourceUsage(
            cpuTimes: $cpuTimes,
            memoryRssBytes: $rssBytes,
            memoryVmsBytes: $vszBytes,
            threadCount: 1,  // ps doesn't provide thread count easily
            openFileDescriptors: 0  // Would need lsof
        );

        return Result::success(new ProcessSnapshot(
            pid: $pid,
            parentPid: $ppid,
            resources: $resources,
            timestamp: new DateTimeImmutable
        ));
    }

    /**
     * Parse time string from ps output (HH:MM:SS or MM:SS.CC) into seconds.
     */
    private function parseTimeString(string $time): float
    {
        // Remove centiseconds if present (e.g., "00:01:23.45" -> "00:01:23")
        if (str_contains($time, '.')) {
            $parts = explode('.', $time);
            $time = $parts[0];
            $centiseconds = isset($parts[1]) ? (float) $parts[1] / 100 : 0.0;
        } else {
            $centiseconds = 0.0;
        }

        $components = explode(':', $time);
        $count = count($components);

        if ($count === 3) {
            // HH:MM:SS format
            return ((int) $components[0] * 3600) + ((int) $components[1] * 60) + (int) $components[2] + $centiseconds;
        } elseif ($count === 2) {
            // MM:SS format
            return ((int) $components[0] * 60) + (int) $components[1] + $centiseconds;
        }

        return 0.0;
    }
}
