<?php

declare(strict_types=1);

namespace PHPeek\SystemMetrics\Actions;

use PHPeek\SystemMetrics\Contracts\ProcessMetricsSource;
use PHPeek\SystemMetrics\DTO\Result;
use PHPeek\SystemMetrics\Sources\Process\CompositeProcessMetricsSource;

/**
 * Action for reading process group metrics (parent + children).
 */
final readonly class ReadProcessGroupMetricsAction
{
    public function __construct(
        private ProcessMetricsSource $source = new CompositeProcessMetricsSource,
    ) {}

    /**
     * Execute the action to read process group metrics.
     *
     * @return Result<\PHPeek\SystemMetrics\DTO\Metrics\Process\ProcessGroupSnapshot>
     */
    public function execute(int $rootPid): Result
    {
        return $this->source->readProcessGroup($rootPid);
    }
}
