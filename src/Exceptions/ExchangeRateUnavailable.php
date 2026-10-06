<?php

declare(strict_types=1);

namespace Nvl\Primitives\Exceptions;

/**
 * @api

 * Reports a currency pair for which no conversion rate is available.
 */
final class ExchangeRateUnavailable extends PrimitivesException
{
    /**
     * Create an exception for one currency pair.
     */
    public static function between(string $from, string $to): self
    {
        return new self("No exchange rate is available for [{$from}/{$to}].");
    }
}
