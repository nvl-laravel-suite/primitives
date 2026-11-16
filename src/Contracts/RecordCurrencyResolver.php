<?php

declare(strict_types=1);

namespace Nvl\Primitives\Contracts;

use Illuminate\Database\Eloquent\Model;
use Nvl\Primitives\ValueObjects\CurrencyCode;

/**
 * Resolves the storage currency for a model attribute and its current raw attributes.
 *
 * @api
 */
interface RecordCurrencyResolver
{
    /** @param array<string,mixed> $attributes */
    public function resolve(Model $model, string $attribute, array $attributes): CurrencyCode;
}
