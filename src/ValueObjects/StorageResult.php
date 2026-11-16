<?php

declare(strict_types=1);

namespace Nvl\Primitives\ValueObjects;

/**
 * Exact amount and currency to persist together after applying a storage policy.
 *
 * @api
 */
final readonly class StorageResult
{
    public function __construct(public Money $money, public CurrencyCode $recordCurrency, public bool $wasMigrated = false) {}
}
