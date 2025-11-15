<?php

use PHPeek\SystemMetrics\Contracts\CpuMetricsSource;
use PHPeek\SystemMetrics\Contracts\EnvironmentDetector;
use PHPeek\SystemMetrics\Contracts\MemoryMetricsSource;
use PHPeek\SystemMetrics\DTO\Environment\Architecture;
use PHPeek\SystemMetrics\DTO\Environment\ArchitectureKind;
use PHPeek\SystemMetrics\DTO\Environment\Cgroup;
use PHPeek\SystemMetrics\DTO\Environment\CgroupVersion;
use PHPeek\SystemMetrics\DTO\Environment\Containerization;
use PHPeek\SystemMetrics\DTO\Environment\ContainerType;
use PHPeek\SystemMetrics\DTO\Environment\EnvironmentSnapshot;
use PHPeek\SystemMetrics\DTO\Environment\Kernel;
use PHPeek\SystemMetrics\DTO\Environment\OperatingSystem;
use PHPeek\SystemMetrics\DTO\Environment\OsFamily;
use PHPeek\SystemMetrics\DTO\Environment\Virtualization;
use PHPeek\SystemMetrics\DTO\Environment\VirtualizationType;
use PHPeek\SystemMetrics\DTO\Metrics\Cpu\CpuSnapshot;
use PHPeek\SystemMetrics\DTO\Metrics\Cpu\CpuTimes;
use PHPeek\SystemMetrics\DTO\Metrics\Memory\MemorySnapshot;
use PHPeek\SystemMetrics\DTO\Result;
use PHPeek\SystemMetrics\Sources\Cpu\CompositeCpuMetricsSource;
use PHPeek\SystemMetrics\Sources\Environment\CompositeEnvironmentDetector;
use PHPeek\SystemMetrics\Sources\Memory\CompositeMemoryMetricsSource;

// Fake sources for testing injection
class FakeCpuSource implements CpuMetricsSource
{
    public function read(): Result
    {
        return Result::success(new CpuSnapshot(
            total: new CpuTimes(100, 0, 50, 200, 0, 0, 0, 0),
            perCore: []
        ));
    }
}

class FakeMemorySource implements MemoryMetricsSource
{
    public function read(): Result
    {
        return Result::success(new MemorySnapshot(
            totalBytes: 1000000,
            freeBytes: 500000,
            availableBytes: 600000,
            usedBytes: 500000,
            buffersBytes: 0,
            cachedBytes: 0,
            swapTotalBytes: 0,
            swapFreeBytes: 0,
            swapUsedBytes: 0
        ));
    }
}

class FakeEnvironmentDetector implements EnvironmentDetector
{
    public function detect(): Result
    {
        return Result::success(new EnvironmentSnapshot(
            os: new OperatingSystem(OsFamily::Linux, 'Fake', '1.0'),
            kernel: new Kernel('5.0', '5.0.0'),
            architecture: new Architecture(ArchitectureKind::X86_64, 'x86_64'),
            virtualization: new Virtualization(VirtualizationType::BareMetal, null, null),
            containerization: new Containerization(ContainerType::None, null, false, null),
            cgroup: new Cgroup(CgroupVersion::None, null, null)
        ));
    }
}

describe('CompositeCpuMetricsSource', function () {
    it('uses injected source when provided', function () {
        $fakeSource = new FakeCpuSource;
        $composite = new CompositeCpuMetricsSource($fakeSource);

        $result = $composite->read();

        expect($result->isSuccess())->toBeTrue();
        expect($result->getValue())->toBeInstanceOf(CpuSnapshot::class);
    });

    it('creates OS-specific source when none provided', function () {
        $composite = new CompositeCpuMetricsSource;

        // This will use the actual OS-specific source (Linux or MacOS)
        // We just verify it returns a Result
        $result = $composite->read();

        expect($result)->toBeInstanceOf(Result::class);
    });

    it('delegates read to underlying source', function () {
        $fakeSource = new FakeCpuSource;
        $composite = new CompositeCpuMetricsSource($fakeSource);

        $result = $composite->read();

        expect($result->isSuccess())->toBeTrue();
        $snapshot = $result->getValue();
        expect($snapshot->total->user)->toBe(100);
        expect($snapshot->total->system)->toBe(50);
    });
});

describe('CompositeMemoryMetricsSource', function () {
    it('uses injected source when provided', function () {
        $fakeSource = new FakeMemorySource;
        $composite = new CompositeMemoryMetricsSource($fakeSource);

        $result = $composite->read();

        expect($result->isSuccess())->toBeTrue();
        expect($result->getValue())->toBeInstanceOf(MemorySnapshot::class);
    });

    it('creates OS-specific source when none provided', function () {
        $composite = new CompositeMemoryMetricsSource;

        // This will use the actual OS-specific source
        $result = $composite->read();

        expect($result)->toBeInstanceOf(Result::class);
    });

    it('delegates read to underlying source', function () {
        $fakeSource = new FakeMemorySource;
        $composite = new CompositeMemoryMetricsSource($fakeSource);

        $result = $composite->read();

        expect($result->isSuccess())->toBeTrue();
        $snapshot = $result->getValue();
        expect($snapshot->totalBytes)->toBe(1000000);
        expect($snapshot->freeBytes)->toBe(500000);
    });
});

describe('CompositeEnvironmentDetector', function () {
    it('uses injected detector when provided', function () {
        $fakeDetector = new FakeEnvironmentDetector;
        $composite = new CompositeEnvironmentDetector($fakeDetector);

        $result = $composite->detect();

        expect($result->isSuccess())->toBeTrue();
        expect($result->getValue())->toBeInstanceOf(EnvironmentSnapshot::class);
    });

    it('creates OS-specific detector when none provided', function () {
        $composite = new CompositeEnvironmentDetector;

        // This will use the actual OS-specific detector
        $result = $composite->detect();

        expect($result)->toBeInstanceOf(Result::class);
    });

    it('delegates detect to underlying detector', function () {
        $fakeDetector = new FakeEnvironmentDetector;
        $composite = new CompositeEnvironmentDetector($fakeDetector);

        $result = $composite->detect();

        expect($result->isSuccess())->toBeTrue();
        $snapshot = $result->getValue();
        expect($snapshot->os->name)->toBe('Fake');
        expect($snapshot->os->version)->toBe('1.0');
    });
});
