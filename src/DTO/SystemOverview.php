<?php

declare(strict_types=1);

namespace PHPeek\SystemMetrics\DTO;

use PHPeek\SystemMetrics\DTO\Environment\EnvironmentSnapshot;
use PHPeek\SystemMetrics\DTO\Metrics\Cpu\CpuSnapshot;
use PHPeek\SystemMetrics\DTO\Metrics\Memory\MemorySnapshot;
use PHPeek\SystemMetrics\DTO\Metrics\Network\NetworkSnapshot;
use PHPeek\SystemMetrics\DTO\Metrics\Storage\StorageSnapshot;

/**
 * Complete overview of the system combining environment, CPU, memory, storage, and network.
 */
final readonly class SystemOverview
{
    public function __construct(
        public EnvironmentSnapshot $environment,
        public CpuSnapshot $cpu,
        public MemorySnapshot $memory,
        public StorageSnapshot $storage,
        public NetworkSnapshot $network,
    ) {}
}
