<?php

declare(strict_types=1);

namespace Nvl\Primitives\Contracts;

use Nvl\Primitives\ValueObjects\CurrencyCode;

/** Supplies the authoritative currency of an Eloquent record.
 * @api
 */
interface HasRecordCurrency
{
    public function getRecordCurrency(): CurrencyCode|string;
}
