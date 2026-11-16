<?php

declare(strict_types=1);

namespace Nvl\Primitives\Services;

use Brick\Math\RoundingMode;
use DateTimeInterface;
use Nvl\Primitives\Enums\StoragePolicy;
use Nvl\Primitives\Exceptions\InvalidPrimitive;
use Nvl\Primitives\Exceptions\ParentCurrencyMigrationRequired;
use Nvl\Primitives\Support\BrickMathCompatibility;
use Nvl\Primitives\ValueObjects\CurrencyCode;
use Nvl\Primitives\ValueObjects\Money;
use Nvl\Primitives\ValueObjects\StorageResult;

/**
 * Prepares exact storage values for a host-controlled currency policy.
 *
 * @api
 */
final readonly class MoneyStorage
{
    public function __construct(private CurrencyConverter $converter) {}

    public function prepare(Money $money, CurrencyCode $recordCurrency, StoragePolicy $policy, ?RoundingMode $roundingMode = null, ?DateTimeInterface $at = null): StorageResult
    {
        $currency = $money->currency();
        if ($currency->equals($recordCurrency)) {
            return new StorageResult($money, $recordCurrency);
        }

        return match ($policy) {
            StoragePolicy::Preserve => new StorageResult($this->converter->convert($money, $recordCurrency, $roundingMode ?? BrickMathCompatibility::unnecessary(), $at), $recordCurrency),
            StoragePolicy::Adopt => new StorageResult($money, $currency, wasMigrated: true),
            StoragePolicy::Require => throw InvalidPrimitive::for('money storage', "record currency [{$recordCurrency}] must match input currency [{$currency}]."),
            StoragePolicy::MigrateParent => throw new ParentCurrencyMigrationRequired($recordCurrency, $currency),
        };
    }
}
