<?php

use PHPeek\SystemMetrics\Config\SystemMetricsConfig;
use PHPeek\SystemMetrics\Contracts\CpuMetricsSource;
use PHPeek\SystemMetrics\Contracts\EnvironmentDetector;
use PHPeek\SystemMetrics\Contracts\MemoryMetricsSource;
use PHPeek\SystemMetrics\Sources\Cpu\CompositeCpuMetricsSource;
use PHPeek\SystemMetrics\Sources\Environment\CompositeEnvironmentDetector;
use PHPeek\SystemMetrics\Sources\Memory\CompositeMemoryMetricsSource;

beforeEach(function () {
    // Reset config before each test to ensure isolation
    SystemMetricsConfig::reset();
});

afterEach(function () {
    // Clean up after each test
    SystemMetricsConfig::reset();
});

describe('SystemMetricsConfig', function () {
    it('returns default EnvironmentDetector when none set', function () {
        $detector = SystemMetricsConfig::getEnvironmentDetector();

        expect($detector)->toBeInstanceOf(EnvironmentDetector::class);
        expect($detector)->toBeInstanceOf(CompositeEnvironmentDetector::class);
    });

    it('returns custom EnvironmentDetector when set', function () {
        $customDetector = new class implements EnvironmentDetector
        {
            public function detect(): \PHPeek\SystemMetrics\DTO\Result
            {
                return \PHPeek\SystemMetrics\DTO\Result::success(
                    new \PHPeek\SystemMetrics\DTO\Environment\EnvironmentSnapshot(
                        os: new \PHPeek\SystemMetrics\DTO\Environment\OperatingSystem(
                            family: \PHPeek\SystemMetrics\DTO\Environment\OsFamily::Linux,
                            name: 'Custom',
                            version: '1.0'
                        ),
                        kernel: new \PHPeek\SystemMetrics\DTO\Environment\Kernel(
                            release: '5.0',
                            version: '5.0.0'
                        ),
                        architecture: new \PHPeek\SystemMetrics\DTO\Environment\Architecture(
                            kind: \PHPeek\SystemMetrics\DTO\Environment\ArchitectureKind::X86_64,
                            raw: 'x86_64'
                        ),
                        virtualization: new \PHPeek\SystemMetrics\DTO\Environment\Virtualization(
                            type: \PHPeek\SystemMetrics\DTO\Environment\VirtualizationType::BareMetal,
                            vendor: \PHPeek\SystemMetrics\DTO\Environment\VirtualizationVendor::Unknown,
                            rawIdentifier: null
                        ),
                        containerization: new \PHPeek\SystemMetrics\DTO\Environment\Containerization(
                            type: \PHPeek\SystemMetrics\DTO\Environment\ContainerType::None,
                            runtime: null,
                            insideContainer: false,
                            rawIdentifier: null
                        ),
                        cgroup: new \PHPeek\SystemMetrics\DTO\Environment\Cgroup(
                            version: \PHPeek\SystemMetrics\DTO\Environment\CgroupVersion::None,
                            cpuPath: null,
                            memoryPath: null
                        )
                    )
                );
            }
        };

        SystemMetricsConfig::setEnvironmentDetector($customDetector);
        $detector = SystemMetricsConfig::getEnvironmentDetector();

        expect($detector)->toBe($customDetector);
    });

    it('returns default CpuMetricsSource when none set', function () {
        $source = SystemMetricsConfig::getCpuMetricsSource();

        expect($source)->toBeInstanceOf(CpuMetricsSource::class);
        expect($source)->toBeInstanceOf(CompositeCpuMetricsSource::class);
    });

    it('returns custom CpuMetricsSource when set', function () {
        $customSource = new class implements CpuMetricsSource
        {
            public function read(): \PHPeek\SystemMetrics\DTO\Result
            {
                return \PHPeek\SystemMetrics\DTO\Result::success(
                    new \PHPeek\SystemMetrics\DTO\Metrics\Cpu\CpuSnapshot(
                        total: new \PHPeek\SystemMetrics\DTO\Metrics\Cpu\CpuTimes(
                            user: 100,
                            nice: 0,
                            system: 50,
                            idle: 200,
                            iowait: 0,
                            irq: 0,
                            softirq: 0,
                            steal: 0
                        ),
                        perCore: []
                    )
                );
            }
        };

        SystemMetricsConfig::setCpuMetricsSource($customSource);
        $source = SystemMetricsConfig::getCpuMetricsSource();

        expect($source)->toBe($customSource);
    });

    it('returns default MemoryMetricsSource when none set', function () {
        $source = SystemMetricsConfig::getMemoryMetricsSource();

        expect($source)->toBeInstanceOf(MemoryMetricsSource::class);
        expect($source)->toBeInstanceOf(CompositeMemoryMetricsSource::class);
    });

    it('returns custom MemoryMetricsSource when set', function () {
        $customSource = new class implements MemoryMetricsSource
        {
            public function read(): \PHPeek\SystemMetrics\DTO\Result
            {
                return \PHPeek\SystemMetrics\DTO\Result::success(
                    new \PHPeek\SystemMetrics\DTO\Metrics\Memory\MemorySnapshot(
                        totalBytes: 1000000,
                        freeBytes: 500000,
                        availableBytes: 600000,
                        buffersBytes: 50000,
                        cachedBytes: 100000,
                        swapTotalBytes: 200000,
                        swapFreeBytes: 100000
                    )
                );
            }
        };

        SystemMetricsConfig::setMemoryMetricsSource($customSource);
        $source = SystemMetricsConfig::getMemoryMetricsSource();

        expect($source)->toBe($customSource);
    });

    it('resets all configuration to defaults', function () {
        $customDetector = new class implements EnvironmentDetector
        {
            public function detect(): \PHPeek\SystemMetrics\DTO\Result
            {
                return \PHPeek\SystemMetrics\DTO\Result::success(
                    new \PHPeek\SystemMetrics\DTO\Environment\EnvironmentSnapshot(
                        os: new \PHPeek\SystemMetrics\DTO\Environment\OperatingSystem(
                            family: \PHPeek\SystemMetrics\DTO\Environment\OsFamily::Linux,
                            name: 'Custom',
                            version: '1.0'
                        ),
                        kernel: new \PHPeek\SystemMetrics\DTO\Environment\Kernel(
                            release: '5.0',
                            version: '5.0.0'
                        ),
                        architecture: new \PHPeek\SystemMetrics\DTO\Environment\Architecture(
                            kind: \PHPeek\SystemMetrics\DTO\Environment\ArchitectureKind::X86_64,
                            raw: 'x86_64'
                        ),
                        virtualization: new \PHPeek\SystemMetrics\DTO\Environment\Virtualization(
                            type: \PHPeek\SystemMetrics\DTO\Environment\VirtualizationType::BareMetal,
                            vendor: \PHPeek\SystemMetrics\DTO\Environment\VirtualizationVendor::Unknown,
                            rawIdentifier: null
                        ),
                        containerization: new \PHPeek\SystemMetrics\DTO\Environment\Containerization(
                            type: \PHPeek\SystemMetrics\DTO\Environment\ContainerType::None,
                            runtime: null,
                            insideContainer: false,
                            rawIdentifier: null
                        ),
                        cgroup: new \PHPeek\SystemMetrics\DTO\Environment\Cgroup(
                            version: \PHPeek\SystemMetrics\DTO\Environment\CgroupVersion::None,
                            cpuPath: null,
                            memoryPath: null
                        )
                    )
                );
            }
        };

        SystemMetricsConfig::setEnvironmentDetector($customDetector);
        SystemMetricsConfig::reset();

        $detector = SystemMetricsConfig::getEnvironmentDetector();
        expect($detector)->toBeInstanceOf(CompositeEnvironmentDetector::class);
        expect($detector)->not->toBe($customDetector);
    });

    it('persists custom configuration across multiple get calls', function () {
        $customDetector = new class implements EnvironmentDetector
        {
            public function detect(): \PHPeek\SystemMetrics\DTO\Result
            {
                return \PHPeek\SystemMetrics\DTO\Result::success(
                    new \PHPeek\SystemMetrics\DTO\Environment\EnvironmentSnapshot(
                        os: new \PHPeek\SystemMetrics\DTO\Environment\OperatingSystem(
                            family: \PHPeek\SystemMetrics\DTO\Environment\OsFamily::Linux,
                            name: 'Custom',
                            version: '1.0'
                        ),
                        kernel: new \PHPeek\SystemMetrics\DTO\Environment\Kernel(
                            release: '5.0',
                            version: '5.0.0'
                        ),
                        architecture: new \PHPeek\SystemMetrics\DTO\Environment\Architecture(
                            kind: \PHPeek\SystemMetrics\DTO\Environment\ArchitectureKind::X86_64,
                            raw: 'x86_64'
                        ),
                        virtualization: new \PHPeek\SystemMetrics\DTO\Environment\Virtualization(
                            type: \PHPeek\SystemMetrics\DTO\Environment\VirtualizationType::BareMetal,
                            vendor: \PHPeek\SystemMetrics\DTO\Environment\VirtualizationVendor::Unknown,
                            rawIdentifier: null
                        ),
                        containerization: new \PHPeek\SystemMetrics\DTO\Environment\Containerization(
                            type: \PHPeek\SystemMetrics\DTO\Environment\ContainerType::None,
                            runtime: null,
                            insideContainer: false,
                            rawIdentifier: null
                        ),
                        cgroup: new \PHPeek\SystemMetrics\DTO\Environment\Cgroup(
                            version: \PHPeek\SystemMetrics\DTO\Environment\CgroupVersion::None,
                            cpuPath: null,
                            memoryPath: null
                        )
                    )
                );
            }
        };

        SystemMetricsConfig::setEnvironmentDetector($customDetector);

        $detector1 = SystemMetricsConfig::getEnvironmentDetector();
        $detector2 = SystemMetricsConfig::getEnvironmentDetector();

        expect($detector1)->toBe($customDetector);
        expect($detector2)->toBe($customDetector);
        expect($detector1)->toBe($detector2);
    });
});
