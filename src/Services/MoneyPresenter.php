<?php

declare(strict_types=1);

namespace Nvl\Primitives\Services;

use Brick\Math\BigInteger;
use Nvl\Primitives\Exceptions\InvalidPrimitive;
use Nvl\Primitives\ValueObjects\Money;
use Nvl\Primitives\ValueObjects\PresentationOptions;

/**
 * Presents exact monetary strings, or opt-in safe numbers, and optional associated values for consumer DTOs.
 *
 * @api
 *
 * @phpstan-type MoneyValue array{amount:string|float,cents:string|int,currency:string,formatted:string}
 */
final readonly class MoneyPresenter
{
    /** JavaScript's Number.MAX_SAFE_INTEGER. */
    private const string MAX_SAFE_INTEGER = '9007199254740991';

    public function __construct(private MoneyFormatter $formatter) {}

    /**
     * @return array{amount:string|float,cents:string|int,currency:string,formatted:string,recordCurrency?:string,recordCents?:string|int,pair?:MoneyValue}
     */
    public function present(Money $money, ?PresentationOptions $options = null): array
    {
        $numeric = $options instanceof PresentationOptions && $options->numeric;
        $result = $this->value($money, $options?->locale, $numeric);
        $record = $options->record ?? $money->source();
        if ($options?->record !== null || ! $money->equals($record)) {
            $result['recordCurrency'] = (string) $record->currency();
            $result['recordCents'] = $numeric ? $this->numericMinor($record) : $record->minorAmount();
        }
        if ($options?->pair !== null) {
            $result['pair'] = $this->value($options->pair, $options->locale, $numeric);
        }

        return $result;
    }

    /** @return MoneyValue */
    private function value(Money $money, ?string $locale, bool $numeric): array
    {
        return [
            'amount' => $numeric ? (float) $money->amount() : $money->amount(),
            'cents' => $numeric ? $this->numericMinor($money) : $money->minorAmount(),
            'currency' => (string) $money->currency(), 'formatted' => $this->formatter->format($money, $locale),
        ];
    }

    /** Return the minor units as an integer that JavaScript can represent exactly. */
    private function numericMinor(Money $money): int
    {
        $minor = BigInteger::of($money->minorAmount());

        if ($minor->abs()->isGreaterThan(self::MAX_SAFE_INTEGER)) {
            throw InvalidPrimitive::for('money presentation', 'amount exceeds the safe integer range');
        }

        return $minor->toInt();
    }
}
