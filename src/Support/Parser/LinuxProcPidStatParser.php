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
 * Parses /proc/{pid}/stat format for process metrics.
 *
 * Format: pid (comm) state ppid pgrp session tty_nr tpgid flags ...
 * Fields: 1   2      3     4    5    6       7      8     9     ...
 *
 * CPU time fields (in ticks):
 * - Field 14: utime (user mode)
 * - Field 15: stime (kernel mode)
 * - Field 16: cutime (children user mode)
 * - Field 17: cstime (children kernel mode)
 *
 * Memory fields:
 * - Field 23: vsize (virtual memory in bytes)
 * - Field 24: rss (resident set size in pages)
 *
 * Thread count:
 * - Field 20: num_threads
 */
final class LinuxProcPidStatParser
{
    /**
     * Parse /proc/{pid}/stat content into ProcessSnapshot.
     *
     * @return Result<ProcessSnapshot>
     */
    public function parse(string $content, int $pid): Result
    {
        $content = trim($content);

        if ($content === '') {
            /** @var Result<ProcessSnapshot> */
            return Result::failure(
                ParseException::forFile("/proc/{$pid}/stat", 'Empty content')
            );
        }

        // Find the last ')' to handle process names with spaces/parentheses
        $closingParen = strrpos($content, ')');
        if ($closingParen === false) {
            /** @var Result<ProcessSnapshot> */
            return Result::failure(
                ParseException::forFile("/proc/{$pid}/stat", 'Invalid format: missing closing parenthesis')
            );
        }

        // Split into fields after the process name
        $afterName = substr($content, $closingParen + 2);
        $fields = preg_split('/\s+/', $afterName);

        if ($fields === false || count($fields) < 22) {
            /** @var Result<ProcessSnapshot> */
            return Result::failure(
                ParseException::forFile("/proc/{$pid}/stat", 'Insufficient fields')
            );
        }

        // Parse fields (adjust indices because we skipped pid and comm)
        $ppid = (int) $fields[1];  // Field 4
        $utime = (int) $fields[11]; // Field 14
        $stime = (int) $fields[12]; // Field 15
        $numThreads = (int) $fields[17]; // Field 20
        $vsize = (int) $fields[20]; // Field 23
        $rss = (int) $fields[21];   // Field 24 (in pages)

        // Convert RSS from pages to bytes (page size is typically 4096)
        $rssBytes = $rss * 4096;

        $cpuTimes = new CpuTimes(
            user: $utime,
            nice: 0,
            system: $stime,
            idle: 0,
            iowait: 0,
            irq: 0,
            softirq: 0,
            steal: 0
        );

        $resources = new ProcessResourceUsage(
            cpuTimes: $cpuTimes,
            memoryRssBytes: $rssBytes,
            memoryVmsBytes: $vsize,
            threadCount: $numThreads,
            openFileDescriptors: 0  // Would need to read /proc/{pid}/fd/
        );

        return Result::success(new ProcessSnapshot(
            pid: $pid,
            parentPid: $ppid,
            resources: $resources,
            timestamp: new DateTimeImmutable
        ));
    }
}
