<?php

declare(strict_types=1);

namespace PHPeek\SystemMetrics\Contracts;

use PHPeek\SystemMetrics\DTO\Result;

/**
 * Interface for reading file contents.
 */
interface FileReaderInterface
{
    /**
     * Read the contents of a file.
     *
     * @return Result<string>
     */
    public function read(string $path): Result;
}
