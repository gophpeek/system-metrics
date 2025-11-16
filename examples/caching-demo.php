#!/usr/bin/env php
<?php

declare(strict_types=1);

require_once __DIR__.'/../vendor/autoload.php';

use PHPeek\SystemMetrics\SystemMetrics;

echo "=== Environment Detection Caching Demo ===\n\n";

// Clear any existing cache
SystemMetrics::clearEnvironmentCache();

// First call - uncached (reads from system)
echo "First call (uncached - reading from disk/syscalls)...\n";
$start1 = microtime(true);
$result1 = SystemMetrics::environment();
$time1 = (microtime(true) - $start1) * 1000; // Convert to milliseconds

if ($result1->isSuccess()) {
    $env = $result1->getValue();
    echo "  ✓ OS: {$env->os->name} {$env->os->version}\n";
    echo "  ✓ Kernel: {$env->kernel->release}\n";
    echo "  ✓ Architecture: {$env->architecture->kind->value}\n";
    echo sprintf("  ✓ Time: %.3f ms\n", $time1);
} else {
    echo "  ✗ Failed: {$result1->getError()->getMessage()}\n";
    exit(1);
}

echo "\n";

// Second call - cached (returns same result instantly)
echo "Second call (cached - no I/O)...\n";
$start2 = microtime(true);
$result2 = SystemMetrics::environment();
$time2 = (microtime(true) - $start2) * 1000;

if ($result2->isSuccess()) {
    echo "  ✓ Same data returned instantly\n";
    echo sprintf("  ✓ Time: %.3f ms\n", $time2);
} else {
    echo "  ✗ Failed: {$result2->getError()->getMessage()}\n";
    exit(1);
}

echo "\n";

// Performance comparison
$speedup = $time1 / $time2;
echo "Performance Improvement:\n";
echo sprintf("  • First call:  %.3f ms\n", $time1);
echo sprintf("  • Second call: %.3f ms (cached)\n", $time2);
echo sprintf("  • Speedup:     %.1fx faster\n", $speedup);
echo sprintf("  • Saved:       %.3f ms per call\n", $time1 - $time2);

echo "\n";

// Multiple calls demonstration
echo "Testing 100 cached calls...\n";
$start = microtime(true);
for ($i = 0; $i < 100; $i++) {
    SystemMetrics::environment();
}
$totalTime = (microtime(true) - $start) * 1000;
$avgTime = $totalTime / 100;

echo sprintf("  ✓ 100 calls completed in %.3f ms\n", $totalTime);
echo sprintf("  ✓ Average per call: %.3f ms\n", $avgTime);

echo "\n";

// Verify dynamic metrics are NOT cached
echo "Verifying dynamic metrics are NOT cached...\n";

$cpu1 = SystemMetrics::cpu();
$cpu2 = SystemMetrics::cpu();

if ($cpu1 !== $cpu2) {
    echo "  ✓ CPU metrics: Different objects (not cached) ✓\n";
} else {
    echo "  ✗ CPU metrics: Same object (incorrectly cached!)\n";
}

$mem1 = SystemMetrics::memory();
$mem2 = SystemMetrics::memory();

if ($mem1 !== $mem2) {
    echo "  ✓ Memory metrics: Different objects (not cached) ✓\n";
} else {
    echo "  ✗ Memory metrics: Same object (incorrectly cached!)\n";
}

echo "\n=== Demo Complete ===\n";
