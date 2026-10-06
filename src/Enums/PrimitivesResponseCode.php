<?php

declare(strict_types=1);

namespace Nvl\Primitives\Enums;

use Nvl\Support\Contracts\ResponseCode;

/** Stable public response discriminators for Primitives.
 * @api
 */
enum PrimitivesResponseCode: string implements ResponseCode
{
    case OperationFailed = 'operation_failed';
    case ExchangeRateStale = 'exchange_rate_stale';
    case ExchangeRateUnavailable = 'exchange_rate_unavailable';
}
