<?php

declare(strict_types=1);

namespace PHPeek\SystemMetrics\Actions;

use PHPeek\SystemMetrics\Contracts\SystemLimitsSource;
use PHPeek\SystemMetrics\DTO\Metrics\SystemLimits;
use PHPeek\SystemMetrics\DTO\Result;
use PHPeek\SystemMetrics\Sources\SystemLimits\CompositeSystemLimitsSource;

/**
 * Read unified system resource limits and current usage.
 *
 * Provides consistent API for resource limits regardless of environment:
 * - Container with cgroups: Returns cgroup limits
 * - Bare metal / VM: Returns host limits
 *
 * Use this for vertical scaling decisions to avoid exceeding limits.
 */
final class ReadSystemLimitsAction
{
    public function __construct(
        private readonly SystemLimitsSource $source = new CompositeSystemLimitsSource,
    ) {}

    /**
     * Execute the action to read system limits.
     *
     * @return Result<SystemLimits>
     */
    public function execute(): Result
    {
        return $this->source->read();
    }
}
