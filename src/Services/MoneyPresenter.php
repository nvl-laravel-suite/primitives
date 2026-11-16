<?php

declare(strict_types=1);

namespace Nvl\Primitives\Services;

use Nvl\Primitives\ValueObjects\Money;
use Nvl\Primitives\ValueObjects\PresentationOptions;

/**
 * Presents exact monetary strings and optional associated values for consumer DTOs.
 *
 * @api
 *
 * @phpstan-type MoneyValue array{amount:string,cents:string,currency:string,formatted:string}
 */
final readonly class MoneyPresenter
{
    public function __construct(private MoneyFormatter $formatter) {}

    /**
     * @return array{amount:string,cents:string,currency:string,formatted:string,recordCurrency?:string,recordCents?:string,pair?:MoneyValue}
     */
    public function present(Money $money, ?PresentationOptions $options = null): array
    {
        $result = $this->value($money, $options?->locale);
        $record = $options->record ?? $money->source();
        if ($options?->record !== null || ! $money->equals($record)) {
            $result['recordCurrency'] = (string) $record->currency();
            $result['recordCents'] = $record->minorAmount();
        }
        if ($options?->pair !== null) {
            $result['pair'] = $this->value($options->pair, $options->locale);
        }

        return $result;
    }

    /** @return MoneyValue */
    private function value(Money $money, ?string $locale): array
    {
        return [
            'amount' => $money->amount(), 'cents' => $money->minorAmount(),
            'currency' => (string) $money->currency(), 'formatted' => $this->formatter->format($money, $locale),
        ];
    }
}
