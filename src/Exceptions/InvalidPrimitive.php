<?php

declare(strict_types=1);

namespace Nvl\Primitives\Exceptions;

use InvalidArgumentException;
use Nvl\Support\Contracts\PackageException;
use Throwable;

/**
 * @api

 * Reports an invalid value at a primitive construction boundary.
 */
final class InvalidPrimitive extends InvalidArgumentException implements PackageException
{
    /**
     * Create a field-specific invalid value exception.
     */
    public static function for(
        string $primitive,
        string $reason,
        ?Throwable $previous = null,
    ): self {
        return new self("Invalid {$primitive}: {$reason}", previous: $previous);
    }
}
