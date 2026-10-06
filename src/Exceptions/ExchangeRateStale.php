<?php

declare(strict_types=1);

namespace Nvl\Primitives\Exceptions;

/**
 * @api

 * Reports a configured exchange rate that exceeded its explicit freshness limit.
 */
final class ExchangeRateStale extends PrimitivesException
{
    public static function forPair(string $pair): self
    {
        return new self("The exchange rate for [{$pair}] is stale.");
    }
}
