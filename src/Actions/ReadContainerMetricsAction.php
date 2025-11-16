<?php

declare(strict_types=1);

namespace PHPeek\SystemMetrics\Actions;

use PHPeek\SystemMetrics\Contracts\ContainerMetricsSource;
use PHPeek\SystemMetrics\DTO\Result;
use PHPeek\SystemMetrics\Sources\Container\CompositeContainerMetricsSource;

/**
 * Read container resource limits and usage.
 */
final readonly class ReadContainerMetricsAction
{
    public function __construct(
        private ?ContainerMetricsSource $source = null,
    ) {}

    /**
     * Execute the action to read container metrics.
     *
     * @return Result<\PHPeek\SystemMetrics\DTO\Metrics\Container\ContainerLimits>
     */
    public function execute(): Result
    {
        $source = $this->source ?? new CompositeContainerMetricsSource;

        return $source->read();
    }
}
