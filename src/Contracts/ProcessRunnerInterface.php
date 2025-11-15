<?php

declare(strict_types=1);

namespace PHPeek\SystemMetrics\Contracts;

use PHPeek\SystemMetrics\DTO\Result;

/**
 * Interface for executing system commands with proper error handling.
 */
interface ProcessRunnerInterface
{
    /**
     * Execute a command and return its output.
     *
     * @return Result<string>
     */
    public function execute(string $command): Result;
}
