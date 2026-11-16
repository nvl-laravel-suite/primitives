<?php

declare(strict_types=1);

namespace Nvl\Primitives\Casts;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;
use JsonException;
use Nvl\Primitives\Contracts\RecordCurrencyResolver;
use Nvl\Primitives\ValueObjects\CurrencyCode;
use Nvl\Primitives\ValueObjects\Money;

/**
 * Persists JSON or exact scalar columns in a fixed or record-resolved currency.
 *
 * @implements CastsAttributes<Money|null, Money|array<string, mixed>|string|int|null>
 *
 * @api
 */
final readonly class MoneyCast implements CastsAttributes
{
    private const string Json = 'json';

    private const string Minor = 'minor';

    private const string Decimal = 'decimal';

    private const string Record = 'record';

    private string $mode;

    private ?CurrencyCode $currency;

    private bool $recordCurrency;

    public bool $withoutObjectCaching;

    /**
     * Create a JSON or scalar money cast with optional exact column mappings.
     *
     * @param  string  $mode  json, minor, or decimal
     * @param  string|null  $currency  ISO code or 'record', required for scalar modes
     */
    public function __construct(
        string $mode = self::Json,
        ?string $currency = null,
        private ?string $minorColumn = null,
        private ?string $decimalColumn = null,
    ) {
        if (! in_array($mode, [self::Json, self::Minor, self::Decimal], true)) {
            throw new InvalidArgumentException("Unsupported money cast mode [{$mode}].");
        }

        if ($mode !== self::Json && $currency === null) {
            throw new InvalidArgumentException(
                "Money cast mode [{$mode}] requires a fixed currency argument or the record currency mode.",
            );
        }

        foreach ([$minorColumn, $decimalColumn] as $column) {
            if ($column !== null && ($mode === self::Json || preg_match('/^[A-Za-z_][A-Za-z0-9_]*$/D', $column) !== 1)) {
                throw new InvalidArgumentException('Money column mappings require scalar modes and plain attribute names.');
            }
        }
        $this->mode = $mode;
        $this->recordCurrency = $currency === self::Record;
        $this->currency = $currency !== null && ! $this->recordCurrency ? CurrencyCode::from($currency) : null;
        $this->withoutObjectCaching = $this->recordCurrency || $minorColumn !== null || $decimalColumn !== null;
    }

    /**
     * Hydrate money from JSON, minor units, or a decimal amount.
     *
     * @param  array<string, mixed>  $attributes
     *
     * @throws JsonException
     */
    public function get(
        Model $model,
        string $key,
        mixed $value,
        array $attributes,
    ): ?Money {
        if ($this->mode !== self::Json) {
            [$minorKey, $decimalKey] = $this->columns($key);
            $minor = $minorKey === $key ? $value : ($minorKey === null ? null : ($attributes[$minorKey] ?? null));
            $decimal = $decimalKey === $key ? $value : ($decimalKey === null ? null : ($attributes[$decimalKey] ?? null));
            if ($minor === null && $decimal === null) {
                return null;
            }
            $currency = $this->resolvedCurrency($model, $key, $attributes)
                ?? throw new InvalidArgumentException("Attribute [{$key}] requires a currency.");
            if ($minor !== null) {
                if (! is_string($minor) && ! is_int($minor)) {
                    throw new InvalidArgumentException("Attribute [{$key}] must contain minor units.");
                }

                return Money::minor($minor, $currency);
            }
            if (is_float($decimal)) {
                if (! is_finite($decimal)) {
                    throw new InvalidArgumentException("Attribute [{$key}] must contain a finite decimal amount.");
                }
                $decimal = number_format(
                    $decimal,
                    $currency->toBrick()->getDefaultFractionDigits(),
                    '.',
                    '',
                );
            }

            if (! is_string($decimal) && ! is_int($decimal)) {
                throw new InvalidArgumentException("Attribute [{$key}] must contain a decimal amount.");
            }

            return Money::of($decimal, $currency);
        }
        if ($value === null) {
            return null;
        }

        $payload = is_string($value)
            ? json_decode($value, true, 512, JSON_THROW_ON_ERROR)
            : $value;

        if (! is_array($payload)) {
            throw new InvalidArgumentException("Attribute [{$key}] must contain a money JSON object.");
        }

        /** @var array<string, mixed> $payload */
        $money = Money::fromArray($payload);
        $this->assertCurrency($money, $key, $this->resolvedCurrency($model, $key, $attributes));

        return $money;
    }

    /**
     * Convert money or accepted input to the configured storage representation.
     *
     * @param  array<string, mixed>  $attributes
     * @return string|array<string,string|null>|null
     *
     * @throws JsonException
     */
    public function set(
        Model $model,
        string $key,
        mixed $value,
        array $attributes,
    ): string|array|null {
        if ($value === null) {
            return $this->scalarStorage($key, null);
        }
        $currency = $this->resolvedCurrency($model, $key, $attributes);

        if ($value instanceof Money) {
            $money = $value;
        } else {
            if (
                $this->mode !== self::Json
                && (is_string($value) || is_int($value))
            ) {
                $currency ??= throw new InvalidArgumentException("Attribute [{$key}] requires a currency.");
                $money = $this->mode === self::Minor
                    ? Money::minor($value, $currency)
                    : Money::of($value, $currency);
            } else {
                if (is_string($value)) {
                    $value = json_decode($value, true, 512, JSON_THROW_ON_ERROR);
                }

                if (! is_array($value)) {
                    throw new InvalidArgumentException(
                        "Attribute [{$key}] must be Money, an accepted scalar, or a money payload.",
                    );
                }

                $payload = [];

                foreach ($value as $payloadKey => $payloadValue) {
                    if (! is_string($payloadKey)) {
                        throw new InvalidArgumentException(
                            "Attribute [{$key}] must contain a money JSON object.",
                        );
                    }

                    $payload[$payloadKey] = $payloadValue;
                }

                $money = Money::fromArray($payload);
            }
        }

        $this->assertCurrency($money, $key, $currency);

        return $this->mode === self::Json ? json_encode($money->toArray(), JSON_THROW_ON_ERROR) : $this->scalarStorage($key, $money);
    }

    /** @return array{?string,?string} */
    private function columns(string $key): array
    {
        $minor = $this->minorColumn ?? ($this->mode === self::Minor ? $key : null);
        $decimal = $this->decimalColumn ?? ($this->mode === self::Decimal ? $key : null);
        if ($minor !== null && $minor === $decimal) {
            throw new InvalidArgumentException('Money decimal and minor units require distinct columns.');
        }

        return [$minor, $decimal];
    }

    /** @return string|array<string,string|null>|null */
    private function scalarStorage(string $key, ?Money $money): string|array|null
    {
        if ($this->minorColumn === null && $this->decimalColumn === null) {
            return $this->mode === self::Minor ? $money?->minorAmount() : $money?->amount();
        }
        [$minor, $decimal] = $this->columns($key);
        $columns = [];
        if ($minor !== null) {
            $columns[$minor] = $money?->minorAmount();
        }
        if ($decimal !== null) {
            $columns[$decimal] = $money?->amount();
        }

        return $columns;
    }

    /** @param array<string,mixed> $attributes */
    private function resolvedCurrency(Model $model, string $key, array $attributes): ?CurrencyCode
    {
        return $this->recordCurrency ? app(RecordCurrencyResolver::class)->resolve($model, $key, $attributes) : $this->currency;
    }

    /**
     * Enforce an explicitly configured currency during hydration and assignment.
     */
    private function assertCurrency(Money $money, string $key, ?CurrencyCode $currency): void
    {
        if ($currency !== null && ! $money->currency()->equals($currency)) {
            throw new InvalidArgumentException(
                "Attribute [{$key}] expects [{$currency}], [{$money->currency()}] given.",
            );
        }
    }
}
