<?php

declare(strict_types=1);

namespace Nvl\Primitives\Exceptions;

use Nvl\Primitives\Enums\PrimitivesResponseCode;
use Nvl\Support\Contracts\RespondableException;
use Nvl\Support\Exceptions\ExceptionResponse;
use Nvl\Support\Traits\InteractsWithPackageFailure;
use RuntimeException;

/** Base public Primitives runtime failure.
 * @api
 */
class PrimitivesException extends RuntimeException implements RespondableException
{
    use InteractsWithPackageFailure;

    /** Resolve the declared safe failure for this native hierarchy. */
    protected function exceptionResponse(): ExceptionResponse
    {
        return match (static::class) {
            ExchangeRateStale::class => new ExceptionResponse('primitives', PrimitivesResponseCode::ExchangeRateStale, 409),
            ExchangeRateUnavailable::class => new ExceptionResponse('primitives', PrimitivesResponseCode::ExchangeRateUnavailable, 422),
            default => new ExceptionResponse('primitives', PrimitivesResponseCode::OperationFailed),
        };
    }
}
