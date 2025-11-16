<?php

declare(strict_types=1);

namespace PHPeek\SystemMetrics\Contracts;

use PHPeek\SystemMetrics\DTO\Result;

/**
 * Contract for reading container resource limits and usage.
 */
interface ContainerMetricsSource
{
    /**
     * Read container limits and usage from cgroups.
     *
     * @return Result<\PHPeek\SystemMetrics\DTO\Metrics\Container\ContainerLimits>
     */
    public function read(): Result;
}
