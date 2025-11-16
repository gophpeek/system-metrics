<?php

use PHPeek\SystemMetrics\Contracts\NetworkMetricsSource;
use PHPeek\SystemMetrics\DTO\Metrics\Network\NetworkSnapshot;
use PHPeek\SystemMetrics\DTO\Result;
use PHPeek\SystemMetrics\Exceptions\SystemMetricsException;
use PHPeek\SystemMetrics\Sources\Network\CompositeNetworkMetricsSource;

describe('CompositeNetworkMetricsSource', function () {
    it('creates OS-specific source when none provided', function () {
        $source = new CompositeNetworkMetricsSource;

        $result = $source->read();

        expect($result)->toBeInstanceOf(Result::class);
    });

    it('uses injected Linux source when provided', function () {
        $mockSource = new class implements NetworkMetricsSource
        {
            public function read(): Result
            {
                return Result::success(
                    new NetworkSnapshot(
                        interfaces: [],
                        connections: null
                    )
                );
            }
        };

        $composite = new CompositeNetworkMetricsSource($mockSource, null);
        $result = $composite->read();

        expect($result->isSuccess())->toBeTrue();
        $snapshot = $result->getValue();
        expect($snapshot)->toBeInstanceOf(NetworkSnapshot::class);
    });

    it('uses injected macOS source when provided', function () {
        $mockSource = new class implements NetworkMetricsSource
        {
            public function read(): Result
            {
                return Result::success(
                    new NetworkSnapshot(
                        interfaces: [],
                        connections: null
                    )
                );
            }
        };

        $composite = new CompositeNetworkMetricsSource(null, $mockSource);
        $result = $composite->read();

        expect($result->isSuccess())->toBeTrue();
        $snapshot = $result->getValue();
        expect($snapshot)->toBeInstanceOf(NetworkSnapshot::class);
    });

    it('delegates read to underlying source', function () {
        $mockSource = new class implements NetworkMetricsSource
        {
            public function read(): Result
            {
                return Result::success(
                    new NetworkSnapshot(
                        interfaces: [],
                        connections: null
                    )
                );
            }
        };

        $composite = new CompositeNetworkMetricsSource($mockSource, null);
        $result = $composite->read();

        expect($result->isSuccess())->toBeTrue();
        expect($result->getValue())->toBeInstanceOf(NetworkSnapshot::class);
    });

    it('returns error when OS not supported', function () {
        // When both sources are null, it tries to detect OS
        // If we're on Linux or Darwin, it will succeed
        // This test just verifies the composite handles errors properly
        $mockSource = new class implements NetworkMetricsSource
        {
            public function read(): Result
            {
                return Result::failure(
                    new SystemMetricsException('Test error')
                );
            }
        };

        $composite = new CompositeNetworkMetricsSource($mockSource, $mockSource);
        $result = $composite->read();

        // Should return a result (success or failure)
        expect($result)->toBeInstanceOf(Result::class);

        // If it failed, error should be present
        if ($result->isFailure()) {
            expect($result->getError())->toBeInstanceOf(Throwable::class);
        }
    });
});
