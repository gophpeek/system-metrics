<?php

declare(strict_types=1);

namespace PHPeek\SystemMetrics\Sources\Storage;

use PHPeek\SystemMetrics\Contracts\StorageMetricsSource;
use PHPeek\SystemMetrics\DTO\Result;
use PHPeek\SystemMetrics\Exceptions\SystemMetricsException;

/**
 * Fallback storage metrics source that tries multiple sources in order.
 *
 * This source implements a fallback strategy where it tries each source
 * in priority order, returning the first successful result.
 */
final class FallbackStorageMetricsSource implements StorageMetricsSource
{
    /**
     * @param  StorageMetricsSource[]  $sources  Sources to try in priority order
     */
    public function __construct(
        private readonly array $sources,
    ) {}

    public function read(): Result
    {
        $errors = [];

        foreach ($this->sources as $index => $source) {
            $result = $source->read();

            if ($result->isSuccess()) {
                return $result;
            }

            $error = $result->getError();
            assert($error !== null);
            $errors[] = sprintf(
                'Source %d (%s): %s',
                $index,
                $source::class,
                $error->getMessage()
            );
        }

        /** @var Result<\PHPeek\SystemMetrics\DTO\Metrics\Storage\StorageSnapshot> */
        return Result::failure(
            new SystemMetricsException(
                'All storage metrics sources failed: '.implode('; ', $errors)
            )
        );
    }
}
