<?php

declare(strict_types=1);

namespace Nvl\Primitives\Exceptions;

use LogicException;
use Nvl\Primitives\ValueObjects\CurrencyCode;
use Nvl\Support\Contracts\PackageException;

/**
 * Signals that the host must coordinate a parent currency change before saving.
 *
 * @api
 */
final class ParentCurrencyMigrationRequired extends LogicException implements PackageException
{
    public function __construct(public readonly CurrencyCode $recordCurrency, public readonly CurrencyCode $inputCurrency)
    {
        parent::__construct("Migrate the parent currency from [{$recordCurrency}] to [{$inputCurrency}] before saving this amount.");
    }
}
