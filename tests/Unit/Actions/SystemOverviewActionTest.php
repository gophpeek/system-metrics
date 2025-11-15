<?php

declare(strict_types=1);

use PHPeek\SystemMetrics\Actions\DetectEnvironmentAction;
use PHPeek\SystemMetrics\Actions\ReadCpuMetricsAction;
use PHPeek\SystemMetrics\Actions\ReadMemoryMetricsAction;
use PHPeek\SystemMetrics\Actions\SystemOverviewAction;
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
use PHPeek\SystemMetrics\DTO\SystemOverview;
use PHPeek\SystemMetrics\Exceptions\SystemMetricsException;

// Test doubles
class FakeSuccessEnvironmentDetector implements EnvironmentDetector
{
    public function detect(): Result
    {
        return Result::success(
            new EnvironmentSnapshot(
                os: new OperatingSystem(
                    family: OsFamily::Linux,
                    name: 'Ubuntu',
                    version: '22.04'
                ),
                kernel: new Kernel(
                    release: '5.15.0',
                    version: '#1 SMP'
                ),
                architecture: new Architecture(
                    kind: ArchitectureKind::X86_64,
                    raw: 'x86_64'
                ),
                virtualization: new Virtualization(
                    type: VirtualizationType::BareMetal,
                    vendor: null,
                    rawIdentifier: null
                ),
                containerization: new Containerization(
                    type: ContainerType::None,
                    runtime: null,
                    insideContainer: false,
                    rawIdentifier: null
                ),
                cgroup: new Cgroup(
                    version: CgroupVersion::V2,
                    cpuPath: '/',
                    memoryPath: '/'
                )
            )
        );
    }
}

class FakeFailureEnvironmentDetector implements EnvironmentDetector
{
    public function detect(): Result
    {
        return Result::failure(new SystemMetricsException('Environment detection failed'));
    }
}

class FakeSuccessCpuSource implements CpuMetricsSource
{
    public function read(): Result
    {
        return Result::success(
            new CpuSnapshot(
                total: new CpuTimes(1000, 100, 500, 8000, 200, 50, 150, 0),
                perCore: []
            )
        );
    }
}

class FakeFailureCpuSource implements CpuMetricsSource
{
    public function read(): Result
    {
        return Result::failure(new SystemMetricsException('CPU metrics read failed'));
    }
}

class FakeSuccessMemorySource implements MemoryMetricsSource
{
    public function read(): Result
    {
        return Result::success(
            new MemorySnapshot(
                totalBytes: 16000000000,
                freeBytes: 4000000000,
                availableBytes: 8000000000,
                usedBytes: 8000000000,
                buffersBytes: 1000000000,
                cachedBytes: 2000000000,
                swapTotalBytes: 2000000000,
                swapFreeBytes: 1500000000,
                swapUsedBytes: 500000000
            )
        );
    }
}

class FakeFailureMemorySource implements MemoryMetricsSource
{
    public function read(): Result
    {
        return Result::failure(new SystemMetricsException('Memory metrics read failed'));
    }
}

describe('SystemOverviewAction', function () {
    it('collects complete system overview successfully', function () {
        $environmentAction = new DetectEnvironmentAction(new FakeSuccessEnvironmentDetector);
        $cpuAction = new ReadCpuMetricsAction(new FakeSuccessCpuSource);
        $memoryAction = new ReadMemoryMetricsAction(new FakeSuccessMemorySource);

        $action = new SystemOverviewAction($environmentAction, $cpuAction, $memoryAction);
        $result = $action->execute();

        expect($result->isSuccess())->toBeTrue();
        $overview = $result->getValue();
        expect($overview)->toBeInstanceOf(SystemOverview::class);
        expect($overview->environment)->toBeInstanceOf(EnvironmentSnapshot::class);
        expect($overview->cpu)->toBeInstanceOf(CpuSnapshot::class);
        expect($overview->memory)->toBeInstanceOf(MemorySnapshot::class);
    });

    it('propagates environment detection failure', function () {
        $environmentAction = new DetectEnvironmentAction(new FakeFailureEnvironmentDetector);
        $cpuAction = new ReadCpuMetricsAction(new FakeSuccessCpuSource);
        $memoryAction = new ReadMemoryMetricsAction(new FakeSuccessMemorySource);

        $action = new SystemOverviewAction($environmentAction, $cpuAction, $memoryAction);
        $result = $action->execute();

        expect($result->isFailure())->toBeTrue();
        expect($result->getError())->toBeInstanceOf(SystemMetricsException::class);
        expect($result->getError()->getMessage())->toBe('Environment detection failed');
    });

    it('propagates CPU metrics failure', function () {
        $environmentAction = new DetectEnvironmentAction(new FakeSuccessEnvironmentDetector);
        $cpuAction = new ReadCpuMetricsAction(new FakeFailureCpuSource);
        $memoryAction = new ReadMemoryMetricsAction(new FakeSuccessMemorySource);

        $action = new SystemOverviewAction($environmentAction, $cpuAction, $memoryAction);
        $result = $action->execute();

        expect($result->isFailure())->toBeTrue();
        expect($result->getError())->toBeInstanceOf(SystemMetricsException::class);
        expect($result->getError()->getMessage())->toBe('CPU metrics read failed');
    });

    it('propagates memory metrics failure', function () {
        $environmentAction = new DetectEnvironmentAction(new FakeSuccessEnvironmentDetector);
        $cpuAction = new ReadCpuMetricsAction(new FakeSuccessCpuSource);
        $memoryAction = new ReadMemoryMetricsAction(new FakeFailureMemorySource);

        $action = new SystemOverviewAction($environmentAction, $cpuAction, $memoryAction);
        $result = $action->execute();

        expect($result->isFailure())->toBeTrue();
        expect($result->getError())->toBeInstanceOf(SystemMetricsException::class);
        expect($result->getError()->getMessage())->toBe('Memory metrics read failed');
    });
});
