<?php

declare(strict_types=1);

use PHPeek\SystemMetrics\DTO\Environment\EnvironmentSnapshot;
use PHPeek\SystemMetrics\DTO\Metrics\Cpu\CpuSnapshot;
use PHPeek\SystemMetrics\DTO\Metrics\LoadAverageSnapshot;
use PHPeek\SystemMetrics\DTO\Metrics\Memory\MemorySnapshot;
use PHPeek\SystemMetrics\DTO\Result;
use PHPeek\SystemMetrics\DTO\SystemOverview;
use PHPeek\SystemMetrics\SystemMetrics;

describe('SystemMetrics', function () {
    it('can read environment', function () {
        $result = SystemMetrics::environment();

        expect($result)->toBeInstanceOf(Result::class);
        if ($result->isSuccess()) {
            expect($result->getValue())->toBeInstanceOf(EnvironmentSnapshot::class);
        }
    });

    it('can read CPU metrics', function () {
        $result = SystemMetrics::cpu();

        expect($result)->toBeInstanceOf(Result::class);
        if ($result->isSuccess()) {
            expect($result->getValue())->toBeInstanceOf(CpuSnapshot::class);
        }
    });

    it('can read memory metrics', function () {
        $result = SystemMetrics::memory();

        expect($result)->toBeInstanceOf(Result::class);
        if ($result->isSuccess()) {
            expect($result->getValue())->toBeInstanceOf(MemorySnapshot::class);
        }
    });

    it('can read load average', function () {
        $result = SystemMetrics::loadAverage();

        expect($result)->toBeInstanceOf(Result::class);
        if ($result->isSuccess()) {
            expect($result->getValue())->toBeInstanceOf(LoadAverageSnapshot::class);
        }
    });

    it('can read system overview', function () {
        $result = SystemMetrics::overview();

        expect($result)->toBeInstanceOf(Result::class);
        if ($result->isSuccess()) {
            expect($result->getValue())->toBeInstanceOf(SystemOverview::class);
        }
    });

    it('load average returns valid snapshot on success', function () {
        $result = SystemMetrics::loadAverage();

        if ($result->isSuccess()) {
            $load = $result->getValue();
            expect($load->oneMinute)->toBeFloat();
            expect($load->fiveMinutes)->toBeFloat();
            expect($load->fifteenMinutes)->toBeFloat();
            expect($load->oneMinute)->toBeGreaterThanOrEqual(0.0);
            expect($load->fiveMinutes)->toBeGreaterThanOrEqual(0.0);
            expect($load->fifteenMinutes)->toBeGreaterThanOrEqual(0.0);
        }
    });
});
