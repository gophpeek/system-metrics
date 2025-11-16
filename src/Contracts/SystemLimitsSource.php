<?php

declare(strict_types=1);

namespace PHPeek\SystemMetrics\Contracts;

use PHPeek\SystemMetrics\DTO\Metrics\SystemLimits;
use PHPeek\SystemMetrics\DTO\Result;

/**
 * Contract for retrieving unified system resource limits.
 */
interface SystemLimitsSource
{
    /**
     * Read system resource limits and current usage.
     *
     * Returns limits from cgroups if running in container,
     * otherwise returns host limits.
     *
     * @return Result<SystemLimits>
     */
    public function read(): Result;
}
