<?php

declare(strict_types=1);

namespace Nvl\Primitives\Services;

use Illuminate\Database\Eloquent\Model;
use Nvl\Primitives\Contracts\HasRecordCurrency;
use Nvl\Primitives\Contracts\RecordCurrencyResolver;
use Nvl\Primitives\Exceptions\InvalidPrimitive;
use Nvl\Primitives\ValueObjects\CurrencyCode;

/** @internal */
final class ModelRecordCurrencyResolver implements RecordCurrencyResolver
{
    /** @param array<string,mixed> $attributes */
    public function resolve(Model $model, string $attribute, array $attributes): CurrencyCode
    {
        $currency = $model instanceof HasRecordCurrency ? $model->getRecordCurrency() : ($attributes['currency'] ?? null);
        if ($currency instanceof CurrencyCode) {
            return $currency;
        }
        if (! is_string($currency)) {
            throw InvalidPrimitive::for('record currency', "attribute [{$attribute}] requires an explicit record currency.");
        }

        return CurrencyCode::from($currency);
    }
}
