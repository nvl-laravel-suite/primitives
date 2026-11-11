<?php

declare(strict_types=1);

namespace Nvl\Primitives\Support;

use Nvl\Primitives\Exceptions\InvalidPrimitive;

/**
 * Refuses decimal input whose exponent would expand into an enormous number.
 *
 * @internal
 */
final class DecimalInput
{
    /**
     * Reject scientific notation beyond three exponent digits before Brick Math expands it.
     */
    public static function assertBounded(string|int $value, string $primitive): void
    {
        if (is_string($value) && preg_match('/[eE][+-]?\d{4,}\z/', $value) === 1) {
            throw InvalidPrimitive::for($primitive, 'the exponent is out of range.');
        }
    }
}
