<?php

declare(strict_types=1);

use Illuminate\Contracts\Config\Repository;
use Illuminate\Support\Facades\Validator;
use Nvl\Primitives\Contracts\ExchangeRateProvider;
use Nvl\Primitives\Enums\StoragePolicy;
use Nvl\Primitives\Exceptions\ExchangeRateStale;
use Nvl\Primitives\Exceptions\ExchangeRateUnavailable;
use Nvl\Primitives\Exceptions\InvalidPrimitive;
use Nvl\Primitives\Exceptions\ParentCurrencyMigrationRequired;
use Nvl\Primitives\Providers\PrimitivesServiceProvider;
use Nvl\Primitives\Rules\ValidPhoneNumber;
use Nvl\Primitives\Rules\ValidPrimitive;
use Nvl\Primitives\Services\ConfiguredExchangeRateProvider;
use Nvl\Primitives\Services\CurrencyConverter;
use Nvl\Primitives\Services\MoneyFormatter;
use Nvl\Primitives\Services\MoneyPresenter;
use Nvl\Primitives\Services\MoneyStorage;
use Nvl\Primitives\Services\ReferenceCatalog;
use Nvl\Primitives\Support\BrickMathCompatibility;
use Nvl\Primitives\ValueObjects\CurrencyCode;
use Nvl\Primitives\ValueObjects\EmailAddress;
use Nvl\Primitives\ValueObjects\Money;
use Nvl\Primitives\ValueObjects\Percentage;
use Nvl\Primitives\ValueObjects\PresentationOptions;
use Nvl\Support\Contracts\LocaleCatalog;

it('preserves original money units through conversions and separates record storage decisions', function (): void {
    $original = Money::minor(125, 'EUR');
    $rounding = BrickMathCompatibility::halfUp();
    $display = $original->convert('USD', '2', $rounding)->convert('GBP', '0.5', $rounding);
    $record = CurrencyCode::from('EUR');
    config()->set('nvl-primitives.exchange_rates.rates', ['GBP/EUR' => '2']);
    $storage = app(MoneyStorage::class);

    expect($display->source()->toArray())->toBe(['minor' => '125', 'currency' => 'EUR'])
        ->and($display->toArray())->toBe(['minor' => '125', 'currency' => 'GBP'])
        ->and($storage->prepare($display, $record, StoragePolicy::Preserve)->money->minorAmount())->toBe('250')
        ->and($storage->prepare($display, $record, StoragePolicy::Adopt)->recordCurrency->storageValue())->toBe('GBP')
        ->and($storage->prepare($display, $record, StoragePolicy::Adopt)->wasMigrated)->toBeTrue()
        ->and(fn () => $storage->prepare($display, $record, StoragePolicy::Require))->toThrow(InvalidPrimitive::class)
        ->and(fn () => $storage->prepare($display, $record, StoragePolicy::MigrateParent))->toThrow(ParentCurrencyMigrationRequired::class);

    $presented = app(MoneyPresenter::class)->present($display, new PresentationOptions(locale: 'en', pair: $original));
    expect($presented)->toMatchArray([
        'amount' => '1.25', 'cents' => '125', 'currency' => 'GBP', 'recordCurrency' => 'EUR', 'recordCents' => '125',
    ])->and($presented['pair'])->toMatchArray(['amount' => '1.25', 'cents' => '125', 'currency' => 'EUR'])
        ->and($presented['formatted'])->toBeString()
        ->and(app(MoneyPresenter::class)->present(Money::minor('9007199254740993', 'EUR')))->toMatchArray([
            'cents' => '9007199254740993', 'formatted' => '90071992547409.93 EUR',
        ]);
});

it('rejects boolean and floating point input through scalar primitive validation', function (mixed $value): void {
    expect(Validator::make(['ratio' => $value], ['ratio' => [new ValidPrimitive(Percentage::class)]])->fails())->toBeTrue();
})->with([true, false, 1.25, 1.0000000000000002]);

it('requires configured exchange rates in the requested direction', function (): void {
    config()->set('nvl-primitives.exchange_rates.rates', ['EUR/USD' => '1.10']);
    $provider = app(ConfiguredExchangeRateProvider::class);

    expect($provider->rate(CurrencyCode::from('EUR'), CurrencyCode::from('USD')))->toBe('1.10')
        ->and(fn () => $provider->rate(CurrencyCode::from('USD'), CurrencyCode::from('EUR')))
        ->toThrow(ExchangeRateUnavailable::class);
});

it('preserves a consumer exchange-rate binding when the package provider registers', function (): void {
    $consumerProvider = new class implements ExchangeRateProvider
    {
        public function rate(
            CurrencyCode $from,
            CurrencyCode $to,
            ?DateTimeInterface $at = null,
        ): string {
            return '1.25';
        }
    };

    app()->instance(ExchangeRateProvider::class, $consumerProvider);
    app()->register(PrimitivesServiceProvider::class, true);

    expect(app(ExchangeRateProvider::class))->toBe($consumerProvider)
        ->and(app(CurrencyConverter::class)->convert(
            Money::of('2.00', 'EUR'),
            'USD',
            BrickMathCompatibility::halfUp(),
        )->amount())->toBe('2.50');
});

it('validates configured exchange-rate types and freshness metadata', function (
    mixed $configured,
    string $exception,
): void {
    config()->set('nvl-primitives.exchange_rates.rates', ['EUR/USD' => $configured]);
    $provider = app(ConfiguredExchangeRateProvider::class);

    expect(fn () => $provider->rate(
        CurrencyCode::from('EUR'),
        CurrencyCode::from('USD'),
        new DateTimeImmutable('2026-07-29T12:00:00Z'),
    ))->toThrow($exception);
})->with([
    'boolean rate' => [true, InvalidPrimitive::class],
    'floating-point rate' => [1.1, InvalidPrimitive::class],
    'unbounded scientific exponent rate' => ['1e10000', InvalidPrimitive::class],
    'missing maximum age' => [[
        'rate' => '1.10',
        'as_of' => '2026-07-29T11:59:30Z',
    ], InvalidPrimitive::class],
    'invalid effective instant' => [[
        'rate' => '1.10',
        'as_of' => 'tomorrow',
        'max_age_seconds' => 60,
    ], InvalidPrimitive::class],
    'future effective instant' => [[
        'rate' => '1.10',
        'as_of' => '2026-07-29T12:00:01Z',
        'max_age_seconds' => 60,
    ], ExchangeRateUnavailable::class],
    'stale rate' => [[
        'rate' => '1.10',
        'as_of' => '2026-07-29T11:58:59Z',
        'max_age_seconds' => 60,
    ], ExchangeRateStale::class],
]);

it('requires callers to choose conversion rounding', function (): void {
    config()->set('nvl-primitives.exchange_rates.rates', ['EUR/USD' => '1.005']);

    expect(app(CurrencyConverter::class)->convert(
        Money::of('1.00', 'EUR'),
        'USD',
        BrickMathCompatibility::halfUp(),
    )->amount())->toBe('1.01');
});

it('formats money through an injected service with a deterministic fallback', function (): void {
    $formatter = app(MoneyFormatter::class);
    $formatted = $formatter->format(Money::of('19.99', 'EUR'), 'en');

    if (extension_loaded('intl')) {
        expect($formatted)->toContain('19.99');
    } else {
        expect($formatted)->toBe('19.99 EUR');
    }
});

it('builds distinct locale labels and fails fast on invalid reference config', function (): void {
    config()->set('nvl-core.locales.supported', ['en', 'en-US', 'zh-Hans-CN', 'es-419']);
    $catalog = app(ReferenceCatalog::class);

    expect(array_column($catalog->locales(displayLocale: 'en'), 'label'))->toBe([
        'English',
        'English (United States)',
        'Chinese (Simplified, China)',
        'Spanish (419)',
    ])->and($catalog->locales(displayLocale: 'en')[2]['metadata'])->toBe([
        'script' => 'Hans',
        'region' => 'CN',
    ])->and(fn () => $catalog->countries(limit: 0))->toThrow(InvalidPrimitive::class);

    config()->set('nvl-primitives.reference.banks', ['INVALID' => ['country' => 'BG']]);

    expect(fn () => $catalog->banks())->toThrow(InvalidPrimitive::class);
});

it('validates the primitive rule class at construction time', function (): void {
    expect(fn () => new ValidPrimitive(stdClass::class))
        ->toThrow(InvalidArgumentException::class);
});

it('uses translated package validation messages', function (): void {
    app()->setLocale('bg');

    $validator = Validator::make([
        'email' => 'invalid',
        'phone' => '123',
    ], [
        'email' => [new ValidPrimitive(EmailAddress::class, 'имейл адрес')],
        'phone' => [new ValidPhoneNumber('BG')],
    ]);

    expect($validator->errors()->first('email'))->toContain('валидна стойност')
        ->and($validator->errors()->first('phone'))->toContain('телефонен номер');
});

it('constructs services through their explicit config dependency', function (): void {
    $repository = app(Repository::class);

    expect(new ConfiguredExchangeRateProvider($repository))->toBeInstanceOf(ConfiguredExchangeRateProvider::class)
        ->and(new ReferenceCatalog($repository, app(LocaleCatalog::class)))->toBeInstanceOf(ReferenceCatalog::class)
        ->and(new MoneyFormatter($repository))->toBeInstanceOf(MoneyFormatter::class);
});

it('covers configured exchange-rate success and rejection boundaries', function (): void {
    $provider = app(ConfiguredExchangeRateProvider::class);
    $eur = CurrencyCode::from('EUR');
    $usd = CurrencyCode::from('USD');

    expect($provider->rate($eur, $eur))->toBe('1');

    config()->set('nvl-primitives.exchange_rates.rates', 'invalid');
    expect(fn () => $provider->rate($eur, $usd))->toThrow(InvalidPrimitive::class);

    config()->set('nvl-primitives.exchange_rates.rates', []);
    expect(fn () => $provider->rate($eur, $usd))->toThrow(ExchangeRateUnavailable::class);

    config()->set('nvl-primitives.exchange_rates.rates', [
        'EUR/USD' => [
            'rate' => '1.10',
            'as_of' => '2026-07-29T11:59:30Z',
            'max_age_seconds' => 60,
        ],
    ]);
    expect($provider->rate($eur, $usd, new DateTimeImmutable('2026-07-29T12:00:00Z')))
        ->toBe('1.10');

    config()->set('nvl-primitives.exchange_rates.rates', ['EUR/USD' => '-1']);
    expect(fn () => $provider->rate($eur, $usd))->toThrow(InvalidPrimitive::class);

    config()->set('nvl-primitives.exchange_rates.rates', ['EUR/USD' => 'invalid']);
    expect(fn () => $provider->rate($eur, $usd))->toThrow(InvalidPrimitive::class);

    config()->set('nvl-primitives.exchange_rates.rates', [
        'EUR/USD' => [
            'rate' => '1.10',
            'as_of' => '2026-07-29T11:59:30Z',
            'max_age_seconds' => -1,
        ],
    ]);
    expect(fn () => $provider->rate($eur, $usd, new DateTimeImmutable('2026-07-29T12:00:00Z')))
        ->toThrow(InvalidPrimitive::class);
});

it('covers converter and formatter configuration boundaries', function (): void {
    config()->set('nvl-primitives.exchange_rates.rates', ['EUR/USD' => '1.1']);
    $converter = app(CurrencyConverter::class);
    $formatter = app(MoneyFormatter::class);
    $formatted = $formatter->format(Money::of('1.00', 'EUR'), 'en');

    expect($converter->convert(
        Money::of('1.00', 'EUR'),
        CurrencyCode::from('USD'),
        BrickMathCompatibility::halfUp(),
    )->amount())->toBe('1.10');

    if (extension_loaded('intl')) {
        expect($formatted)->toContain('1.00');
    } else {
        expect($formatted)->toBe('1.00 EUR');
    }

    config()->set('nvl-primitives.money.default_locale', ['invalid']);
    expect(fn () => $formatter->format(Money::of('1.00', 'EUR')))
        ->toThrow(InvalidPrimitive::class)
        ->and(fn () => $formatter->format(Money::of('1.00', 'EUR'), 'invalid-locale'))
        ->toThrow(InvalidPrimitive::class);
});

it('covers every reference catalog family and malformed configuration', function (): void {
    $catalog = app(ReferenceCatalog::class);
    config()->set('nvl-core.locales.supported', ['en']);
    config()->set('nvl-primitives.reference.cities', [
        'sofia-bg' => [
            'label' => 'Sofia',
            'country' => 'BG',
            'population' => 1_200_000,
            'capital' => true,
        ],
    ]);

    expect($catalog->countries('BG', 'en', 5)[0]['code'])->toBe('BG')
        ->and($catalog->currencies('EUR', 'en', 5)[0]['metadata']['fractionDigits'])->toBe(2)
        ->and($catalog->currencies('EUR', 'en', 5)[0]['code'])->toBe('EUR')
        ->and($catalog->languages('Bulgarian', 'en', 5)[0]['code'])->toBe('bg')
        ->and($catalog->cities('sofia')[0]['metadata'])->toBe([
            'country' => 'BG',
            'population' => 1_200_000,
            'capital' => true,
        ]);

    config()->set('nvl-core.locales.supported', 'invalid');
    expect(fn () => $catalog->locales())->toThrow(InvalidPrimitive::class);

    config()->set('nvl-core.locales.supported', [42]);
    expect(fn () => $catalog->locales())->toThrow(InvalidPrimitive::class);

    config()->set('nvl-primitives.reference.cities', 'invalid');
    expect(fn () => $catalog->cities())->toThrow(InvalidPrimitive::class);

    config()->set('nvl-primitives.reference.cities', [
        'sofia' => ['label' => 'Sofia', 'nested' => []],
    ]);
    expect(fn () => $catalog->cities())->toThrow(InvalidPrimitive::class)
        ->and(fn () => $catalog->countries(limit: 251))->toThrow(InvalidPrimitive::class)
        ->and(fn () => $catalog->countries(displayLocale: 'invalid-locale'))
        ->toThrow(InvalidPrimitive::class);
});

it('covers validation rule success and non-scalar rejection', function (): void {
    $valid = Validator::make([
        'email' => 'person@example.com',
        'phone' => '+41446681800',
    ], [
        'email' => [new ValidPrimitive(EmailAddress::class, 'email address')],
        'phone' => [new ValidPhoneNumber],
    ]);
    $invalid = Validator::make([
        'email' => [],
        'phone' => [],
    ], [
        'email' => [new ValidPrimitive(EmailAddress::class, 'email address')],
        'phone' => [new ValidPhoneNumber],
    ]);

    expect($valid->passes())->toBeTrue()
        ->and($invalid->fails())->toBeTrue()
        ->and($invalid->errors()->has('email'))->toBeTrue()
        ->and($invalid->errors()->has('phone'))->toBeTrue();
});

it('rejects an invalid configured exchange-rate implementation', function (): void {
    config()->set('nvl-primitives.exchange_rates.implementation', stdClass::class);
    $provider = new PrimitivesServiceProvider($this->app);

    expect(fn () => $provider->register())->toThrow(
        InvalidArgumentException::class,
        'nvl-primitives.exchange_rates.implementation must implement ExchangeRateProvider.',
    );
    expect(app(ExchangeRateProvider::class))->toBeInstanceOf(ConfiguredExchangeRateProvider::class);
});

it('presents money as exact strings by default', function (): void {
    $presented = app(MoneyPresenter::class)->present(
        Money::minor(125, 'GBP')->withSource(Money::minor(250, 'EUR')),
        new PresentationOptions(locale: 'en', pair: Money::minor(300, 'USD')),
    );

    expect($presented['amount'])->toBe('1.25')
        ->and($presented['cents'])->toBe('125')
        ->and($presented['recordCents'])->toBe('250')
        ->and($presented['pair']['amount'])->toBe('3.00')
        ->and($presented['pair']['cents'])->toBe('300');
});

it('presents opt-in numeric money for the primary, record and pair values', function (): void {
    $presenter = app(MoneyPresenter::class);
    $money = Money::minor(125, 'GBP')->withSource(Money::minor(250, 'EUR'));
    $pair = Money::minor(300, 'USD');
    $strings = $presenter->present($money, new PresentationOptions(locale: 'en', pair: $pair));
    $numbers = $presenter->present($money, new PresentationOptions(locale: 'en', pair: $pair, numeric: true));

    expect(array_keys($numbers))->toBe(['amount', 'cents', 'currency', 'formatted', 'recordCurrency', 'recordCents', 'pair'])
        ->and($numbers['amount'])->toBe(1.25)
        ->and($numbers['cents'])->toBe(125)
        ->and($numbers['currency'])->toBe('GBP')
        ->and($numbers['formatted'])->toBe($strings['formatted'])
        ->and($numbers['recordCurrency'])->toBe('EUR')
        ->and($numbers['recordCents'])->toBe(250)
        ->and($numbers['pair'])->toBe([
            'amount' => 3.0, 'cents' => 300, 'currency' => 'USD', 'formatted' => $strings['pair']['formatted'],
        ])
        ->and($presenter->present(Money::minor(-7, 'EUR'), new PresentationOptions(numeric: true)))->toBe([
            'amount' => -0.07, 'cents' => -7, 'currency' => 'EUR',
            'formatted' => $presenter->present(Money::minor(-7, 'EUR'))['formatted'],
        ]);
});

it('uses each currency scale for numeric money', function (string $currency, int $minor, float $amount): void {
    $presented = app(MoneyPresenter::class)->present(Money::minor($minor, $currency), new PresentationOptions(numeric: true));

    expect($presented['cents'])->toBe($minor)
        ->and($presented['amount'])->toBe($amount);
})->with([
    'zero-decimal yen' => ['JPY', 1234, 1234.0],
    'three-decimal dinar' => ['KWD', 1234, 1.234],
]);

it('accepts numeric money at the JavaScript safe integer boundary', function (string $minor): void {
    $presenter = app(MoneyPresenter::class);
    $money = Money::minor($minor, 'EUR');
    $presented = $presenter->present($money, new PresentationOptions(record: $money, pair: $money, numeric: true));

    expect($presented['cents'])->toBe((int) $minor)
        ->and($presented['recordCents'])->toBe((int) $minor)
        ->and($presented['pair']['cents'])->toBe((int) $minor);
})->with(['9007199254740991', '-9007199254740991']);

it('rejects numeric money beyond the JavaScript safe integer range', function (string $minor): void {
    $presenter = app(MoneyPresenter::class);
    $unsafe = Money::minor($minor, 'EUR');
    $safe = Money::minor(1, 'EUR');

    expect(fn () => $presenter->present($unsafe, new PresentationOptions(numeric: true)))->toThrow(InvalidPrimitive::class)
        ->and(fn () => $presenter->present($safe, new PresentationOptions(record: $unsafe, numeric: true)))->toThrow(InvalidPrimitive::class)
        ->and(fn () => $presenter->present($safe, new PresentationOptions(pair: $unsafe, numeric: true)))->toThrow(InvalidPrimitive::class)
        ->and($presenter->present($unsafe)['cents'])->toBe($minor);
})->with(['9007199254740992', '-9007199254740992']);
