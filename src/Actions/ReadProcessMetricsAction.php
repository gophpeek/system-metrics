<?php

declare(strict_types=1);

namespace PHPeek\SystemMetrics\Actions;

use PHPeek\SystemMetrics\Contracts\ProcessMetricsSource;
use PHPeek\SystemMetrics\DTO\Result;
use PHPeek\SystemMetrics\Sources\Process\CompositeProcessMetricsSource;

/**
 * Action for reading process metrics.
 */
final readonly class ReadProcessMetricsAction
{
    public function __construct(
        private ProcessMetricsSource $source = new CompositeProcessMetricsSource,
    ) {}

    /**
     * Execute the action to read process metrics.
     *
     * @return Result<\PHPeek\SystemMetrics\DTO\Metrics\Process\ProcessSnapshot>
     */
    public function execute(int $pid): Result
    {
        return $this->source->read($pid);
    }
}
