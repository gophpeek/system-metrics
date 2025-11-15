<?php

declare(strict_types=1);

namespace PHPeek\SystemMetrics\Contracts;

use PHPeek\SystemMetrics\DTO\Result;

/**
 * Contract for reading process-level metrics.
 */
interface ProcessMetricsSource
{
    /**
     * Read metrics for a single process.
     *
     * @return Result<\PHPeek\SystemMetrics\DTO\Metrics\Process\ProcessSnapshot>
     */
    public function read(int $pid): Result;

    /**
     * Read metrics for a process group (parent + all children).
     *
     * @return Result<\PHPeek\SystemMetrics\DTO\Metrics\Process\ProcessGroupSnapshot>
     */
    public function readProcessGroup(int $rootPid): Result;
}
