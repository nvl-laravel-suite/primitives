# NVL Primitives — API and usage

## Quickstart

```sh
composer require nvl/primitives:^5.0
php artisan nvl:install primitives --dry-run
php artisan nvl:install primitives
```

Required NVL dependencies: `nvl/core` (`^5.0`). Use immutable value objects for validated scalar values. No package model factory or database is required; invalid programmer input retains its native exception category.
Review the published common config, select one migration owner, and run schema preflight before existing-table upgrades. The installer does not enable features or run migrations. Follow the detailed installation and capability sections below before invoking a storage/provider operation.

```php
use Nvl\Primitives\ValueObjects\EmailAddress;
$email = EmailAddress::from('reader@example.com');
```

Use the [event catalog](docs/events.md) and [Testing your app](#testing-your-app) below. The suite [getting-started guide](https://github.com/nvl-laravel-suite/laravel-suite/blob/main/docs/getting-started.md) provides a complete Comments host fixture; package archives retain their own local references.


[← NVL Laravel Suite](https://github.com/nvl-laravel-suite)

For support, [open an issue](https://github.com/nvl-laravel-suite/primitives/issues). For vulnerabilities, use
[private reporting](https://github.com/nvl-laravel-suite/primitives/security/advisories/new). See [Contributing](CONTRIBUTING.md).

See the [installation and publishing guide](https://github.com/nvl-laravel-suite/laravel-suite/blob/main/docs/installation.md) for Composer setup, configuration, migration ownership, and agent skills.

## Quick reference

| Item | Value |
|---|---|
| Installed through | `composer require nvl/primitives:^5.0` |
| Module identifier | `nvl/primitives` |
| PHP namespace | `Nvl\Primitives` |
| Service provider | `Nvl\Primitives\Providers\PrimitivesServiceProvider` |
| Configuration | `config/nvl-primitives.php` |

Immutable application value objects, exact money, Eloquent casts, validation rules, currency conversion, and standards-backed reference catalogs for Laravel 13.

## Purpose and boundaries

Primitives replaces the reusable parts of a traditional application “Core” module without becoming a second application framework. It owns:

- immutable value objects and canonical serialization;
- Eloquent casts for scalar, JSON, and monetary values;
- exact money arithmetic and an exchange-rate provider boundary;
- international phone and IBAN parsing backed by maintained registries;
- current ISO country, currency, and language catalogs;
- application locale, city, and bank option boundaries;
- Spatie Data DTOs and generated TypeScript contracts.

It does not own users, settings, authorization, controllers, application routes, UI components, live exchange-rate HTTP clients, or a global cities/banks database.

## Requirements and installation

- PHP 8.4+
- Laravel 13
- `ext-mbstring`, `ext-json`, and `ext-ctype`
- optional `ext-intl` for locale-aware money formatting

The package uses Brick Money/Math, Google's numbering metadata through `giggsey/libphonenumber-for-php`, the SWIFT-derived IBAN registry through `jschaedl/iban-validation`, and Symfony Intl data.

```bash
composer require nvl/primitives:^5.0
php artisan vendor:publish --tag=nvl-primitives-config
php artisan vendor:publish --tag=nvl-primitives-translations
php artisan vendor:publish --tag=nvl-primitives-skills
```

The package has no migrations and works without a database.
The translation tag publishes validation overrides to
`lang/vendor/primitives` while package defaults continue to load directly.

## Value objects

| Value object | Canonical storage | Important behavior |
|---|---|---|
| `EmailAddress` | validated address string | normalizes the domain and masks for display |
| `Url` | absolute HTTP(S) string | normalizes scheme, host, and default ports; rejects credentials |
| `CountryCode` | ISO alpha-2 uppercase string | validates current country data |
| `CurrencyCode` | known ISO 4217 uppercase string | name, symbol, and fraction digits |
| `LocaleCode` | normalized BCP 47 core tag | enforces language, script, region, then variants |
| `PhoneNumber` | E.164 string | international parsing and formatting |
| `Iban` | electronic IBAN string | country format/checksum and masking |
| `DateTimeValue` | `Y-m-d\TH:i:s.u\Z` | strict RFC 3339 input and canonical UTC output |
| `Percentage` | exact decimal ratio | exact percentage calculations |
| `Weight` | exact grams | kilograms, pounds, and ounces |
| `Length` | JSON metres | explicit scale and rounding for unit conversion |
| `Coordinates` | JSON string coordinates | WGS84 validation and distance |
| `PostalAddress` | JSON object | international components with an optional postal code |
| `Money` | `{"minor":"…","currency":"…"}` | exact arithmetic and explicit conversion rounding |

Every primitive is immutable, JSON serializable, stringable, and type-safe for equality.

## Eloquent casts

Declare the value-object class directly:

```php
protected function casts(): array
{
    return [
        'email' => EmailAddress::class,
        'phone' => PhoneNumber::class,
        'coordinates' => Coordinates::class,
        'price' => Money::class,
    ];
}
```

Scalar primitives use one string/decimal column. `Coordinates`, `PostalAddress`, and default `Money` use JSON. Null remains null; malformed persisted values fail during hydration.

### Money storage

Default JSON contains only integer minor units and currency:

```json
{"minor":"1999","currency":"EUR"}
```

For an explicitly fixed-currency schema:

```php
protected function casts(): array
{
    return [
        'price_minor' => Money::class.':minor,EUR',
        'price_decimal' => Money::class.':decimal,EUR',
    ];
}
```

Fixed modes accept a `Money` instance or a scalar representing that column's minor/decimal value. The cast rejects a different currency. An explicit `Money::class.':json,EUR'` currency is also enforced on both assignment and hydration. For variable-currency relational schemas, keep amount and currency in separate application-owned columns and construct `Money` at the model boundary.

SQLite hydrates `DECIMAL` columns as floating-point values. Decimal-mode casts normalize only those database-hydrated floats to the currency's fraction digits; assigning a float remains invalid. Prefer JSON or fixed-currency minor-unit storage when amounts may exceed SQLite's exact numeric range.

## Exact money

Construct with decimal strings or integer minor units—never floats:

```php
use Brick\Math\RoundingMode;
use Nvl\Primitives\Services\MoneyFormatter;
use Nvl\Primitives\ValueObjects\Money;

$price = Money::of('19.99', 'EUR');
$shipping = Money::minor(500, 'EUR');

$total = $price->add($shipping);               // EUR 24.99
$triple = $price->multiply(3);                 // EUR 59.97
$share = $price->divide(3, RoundingMode::HalfUp);

$total->amount();                              // "24.99"
$total->minorAmount();                         // "2499"
$total->currency();                            // CurrencyCode("EUR")

$formatter->format($total, 'de-DE');           // locale-aware with ext-intl
```

Currency is always explicit. An unrepresentable amount or operation throws unless a rounding mode is explicit. Arithmetic across currencies fails rather than converting implicitly. `MoneyFormatter` uses its injected configuration repository and falls back deterministically to `amount currency` when native `ext-intl` is unavailable.

### Currency conversion

Configure deterministic rates:

```php
'exchange_rates' => [
    'implementation' => ConfiguredExchangeRateProvider::class,
    'rates' => [
        'EUR/USD' => '1.10',
        'EUR/BGN' => '1.95583',
    ],
],
```

```php
$usd = $converter->convert(
    money: Money::of('100.00', 'EUR'),
    target: 'USD',
    roundingMode: RoundingMode::HalfUp,
);
```

Rates mean “target major units for one source major unit.” Every direction must be configured explicitly; inverse rates are never inferred. Rate values must be positive decimal strings, not floats or booleans. Optional `as_of` and `max_age_seconds` metadata must be supplied together; future, stale, malformed, and missing rates fail explicitly.

For production rates, implement and bind `ExchangeRateProvider`. It may use a database/API but must return an auditable decimal string and respect the optional effective date. Network calls do not belong in `Money` or `CurrencyConverter`.

## Phones and IBANs

```php
$phone = PhoneNumber::fromRegion('044 668 18 00', 'CH');

(string) $phone;            // +41446681800
$phone->international();    // +41 44 668 18 00
$phone->national();         // 044 668 18 00
$phone->rfc3966();          // tel:+41-44-668-18-00
$phone->region();           // CountryCode("CH")

$iban = Iban::from('DE89 3704 0044 0532 0130 00');

$iban->storageValue();      // DE89370400440532013000
$iban->formatted();
$iban->masked();
$iban->country();           // CountryCode("DE")
```

National phone input requires an explicit region or `nvl-primitives.phone.default_region`. Prefer an explicit request-specific region.

## Locale and ISO codes

```php
$country = CountryCode::from('bg');        // BG
$currency = CurrencyCode::from('eur');     // EUR
$locale = LocaleCode::from('zh_hans_cn');  // zh-Hans-CN

$country->name('en');
$currency->symbol('de');
$currency->fractionDigits();
$locale->language();
$locale->script();
$locale->regionCode(); // alpha country or numeric UN M49 region
$locale->region();
```

Locale tags support a language followed by an optional script, optional alpha/numeric region, and valid variant subtags. Extensions and private-use subtags are intentionally outside the package contract. Application-supported locale options come from Core’s `Nvl\Support\Contracts\LocaleCatalog`. Configure `nvl-core.locales`, use Translatable’s adapter, or bind the contract in the host. `nvl-primitives.locales.supported` is deprecated for one major cycle and remains a standalone compatibility fallback only.

## Exact quantities and structured values

```php
$discount = Percentage::fromPercent(20);
$discount->decimal();               // "0.2"
$discount->of('125.00');            // "25"
$discount->ofRounded(
    '125.00',
    2,
    RoundingMode::HalfUp,
);                                  // "25.00"

$weight = Weight::kilograms('1.5');
$weight->inGrams();                 // "1500.000"
$weight->inOunces();                // "52.911"

$length = Length::from('12', 'in');
$length->in('cm', 2, RoundingMode::Unnecessary); // "30.48"

$sofia = Coordinates::from('42.6977', '23.3219');
$plovdiv = Coordinates::from('42.1354', '24.7453');
$sofia->distanceTo($plovdiv);       // kilometres
$sofia->googleMapsUrl();
```

`PostalAddress` keeps components separate for country-specific presentation and permits countries without postal codes. `DateTimeValue` accepts only timezone-qualified RFC 3339 input with valid offset hours and minutes, preserves microseconds, and stores one canonical UTC value; convert only at display boundaries. Coordinate arrays accept numeric strings, integers, or floats and reject booleans. Email normalization preserves the complete local part, including quoted `@` characters, and lowercases only the domain.

## Laravel validation

```php
'email' => [
    'required',
    new ValidPrimitive(EmailAddress::class, 'email address'),
],
'iban' => [
    'nullable',
    new ValidPrimitive(Iban::class, 'IBAN'),
],
'phone' => [
    'required',
    new ValidPhoneNumber('BG'),
],
```

Validation messages ship in English and Bulgarian and use Laravel's translation pipeline. Request validation improves messages, but domain code should still construct the primitive before persistence.

## Reference catalogs

`ReferenceCatalog` returns `ReferenceOption` DTOs:

```php
$countries = $catalog->countries('Bulg', 'en', 20);
$currencies = $catalog->currencies('Euro', 'en');
$languages = $catalog->languages('Bulgarian', 'en');
$locales = $catalog->locales('bg', 'en');
```

Countries, currencies, and languages come from installed Symfony Intl standards data. Locale labels include script and region qualifiers so supported variants remain distinguishable. Cities and banks are deployment-specific, so configure small application lists:

```php
'reference' => [
    'cities' => [
        'sofia-bg' => [
            'label' => 'Sofia',
            'country' => 'BG',
            'region' => 'Sofia City',
        ],
    ],
    'banks' => [
        'UNCRBGSF' => [
            'label' => 'UniCredit Bulbank',
            'country' => 'BG',
        ],
    ],
],
```

Malformed catalog entries and limits outside `1..250` fail immediately. Large or dynamic datasets should use an application service/repository and the same `ReferenceOption` response shape. Primitives intentionally avoids mandatory reference tables, seeders, and unauthenticated routes.

## Spatie Data and TypeScript

The package registers with Core's Data provider. Public contracts include `MoneyData`, `LengthData`, `CoordinatesData`, `PostalAddressData`, and `ReferenceOption`.

```bash
php artisan nvl:data:types:generate
php artisan nvl:data:types:check
```

Expose DTOs rather than Brick, libphonenumber, or IBAN vendor objects in JSON APIs.

## Adoption

- Replace existing money values only after auditing every float construction and making rounding explicit.
- Replace custom locale/country/currency enums or casts with validated codes.
- Replace phone/IBAN regex and checksum logic with registry-backed objects.
- Keep settings in `nvl/settings`; Primitives does not own settings.
- Retain application reference tables only when persistence, translation, or domain metadata is necessary.
- Replace suggestion controllers with application controllers calling `ReferenceCatalog` or an application-owned directory.

Audit data before changing cast modes. JSON, decimal, and minor-unit money columns require an explicit migration.

## Quality

```bash
composer install
composer quality
```

The package gate runs Pint, PHPStan at maximum strictness, and isolated Testbench/Pest tests. Maintainer CI additionally runs dependency analysis, Composer audit, integration tests, and Laravel 13 gates.

See [UPGRADING.md](UPGRADING.md), [SECURITY.md](SECURITY.md), [CONTRIBUTING.md](CONTRIBUTING.md), and [CHANGELOG.md](CHANGELOG.md).

## Supported PHP usage

The source `@api` declarations identify supported workflows, extension contracts, and value types. Public members marked `@internal` and untagged implementation types remain package-owned. Concrete Actions retain their existing constructors, qualifiers, and `execute()` signatures.

A package model returned or accepted by a public workflow is an identity/result handle. Use its declared type and `getKey()`, `getKeyName()`, `getMorphClass()`, `getRouteKey()`, `getRouteKeyName()`, `is()`, `isNot()`, and `relationLoaded()`. Read only explicitly declared in-memory `@nvl-consumer-read` fields; ordinary model PHPDocs and fillable attributes do not grant consumer reads. Obtain display projections through public reads. Persistence, additional model queries, relation access/loading, and generic model serialization are outside this contract. Host-model queries remain available, while traversal or aggregates of package capability relations require the package public reader or authorized adapter.

## Testing your app

Immutable currencies, money, percentages, units, formatters, and catalogs are
direct APIs. This package has no Eloquent models or factories. Test calculations
with native values and substitute only the existing `ExchangeRateProvider` when
an application needs an exchange-rate boundary.

```php
use Brick\Math\RoundingMode;
use Nvl\Primitives\Contracts\ExchangeRateProvider;
use Nvl\Primitives\Services\CurrencyConverter;
use Nvl\Primitives\ValueObjects\CurrencyCode;
use Nvl\Primitives\ValueObjects\Money;

$rates = Mockery::mock(ExchangeRateProvider::class);
$rates->shouldReceive('rate')->once()
    ->withArgs(fn (CurrencyCode $from, CurrencyCode $to, $at): bool =>
        (string) $from === 'EUR' && (string) $to === 'USD' && $at === null)
    ->andReturn('1.10');
$this->app->instance(ExchangeRateProvider::class, $rates);
$money = Money::of('10.00', 'EUR');
$converted = $this->app->make(CurrencyConverter::class)
    ->convert($money, 'USD', RoundingMode::HalfUp);
expect($converted->amount())->toBe('11.00');
```

The configured default already uses a conditional transient binding and retains
host replacements. A host HTTP-backed rate provider can use `Http::fake()` in
its integration tests. Direct value tests and the injected rate test require no
new symmetry interface, package fake, or database fixture.

In Laravel application tests, register a native Mockery interface mock or a small
implementation with `$this->app->instance(Contract::class, $substitute)` before
resolving your application service. A host binding installed before package
registration is retained; later contract replacements affect subsequent
resolutions. Rebuild previously resolved host services after replacing their
dependencies. Concrete implementations remain callable with their original
constructors through major 5. Mocks exercise your application orchestration;
package authorization, persistence, and external effects need real integration
tests.

ExchangeRateProvider already retains host bindings through bindIf. Immutable value construction and calculations remain direct; no new interface or factory is introduced.

For static consumer checks, include the shipped
[`consumer-audit.neon`](https://github.com/nvl-laravel-suite/core/blob/main/support/consumer-audit.neon) from
`vendor/nvl/core/support/consumer-audit.neon` in your host PHPStan configuration
and configure explicit `nvlConsumer.testPaths` for factory-backed tests. The
extension checks supported APIs and model/query boundaries; it does not prove
authorization or arbitrary dynamic SQL.

## Canonical configuration ownership

Use `nvl-primitives` settings in `config/nvl-primitives.php` and canonical package environment names. Old generic roots are foreign unless an upgrading NVL host explicitly selects them in Core's default-off compatibility. Canonical false/null/empty values win; no old roots are populated or written back. Keep logical package/resource IDs unchanged. Review [Core's rename inventory and cache/worker cutover](https://github.com/nvl-laravel-suite/core/blob/main/UPGRADING.md#major-5-canonical-configuration-and-environment).

## Testing your app

Inject the supported contract rather than constructing its concrete Action or querying package tables. Replace `Nvl\Primitives\Contracts\Primitive` in Laravel's native container for a host-workflow test:

```php
use Nvl\Primitives\Contracts\Primitive;

$double = Mockery::mock(Primitive::class);
$this->app->instance(Primitive::class, $double);
// Configure the exact equals arguments and documented return value for your host case.
```

The package's conditional native binding preserves host substitutions. Production uses the real contract; test doubles do not prove its storage/authorization behavior.

This package has no persistent fixture model in the supported factory inventory. Test value objects and contract inputs directly; do not invent a package model factory.

Use Laravel `Event::fake()`, `Queue::fake()`, `Mail::fake()` or `Storage::fake()` only for the effects the host test intends to isolate. Use real commits/listeners for timing proof. Add the optional Core consumer boundary rules to host PHPStan:

```neon
includes:
    - vendor/nvl/core/support/consumer-audit.neon
parameters:
    nvlConsumer:
        testPaths: [tests]
        tableNames: []
        exceptions: []
```

Rules read installed public metadata without suite boot. They flag internal symbols, package model queries/writes, capability relations and owned tables; they cannot prove dynamic code or runtime authorization. Exact exceptions require `file`, `identifier`, `symbol`, and a documented `reason`. New C3/C4/E tests, archives and guide execution remain pending until the integration phase records results.

## Error codes and events

All recognized package failures implement `Nvl\Support\Contracts\PackageException`; only `RespondableException` opts into safe response metadata. Keep native PHP programmer errors and Laravel/SDK exceptions distinct. The optional `PackageExceptionRenderer` is registered by the host in `withExceptions`; it leaves unrelated, marker-only and non-JSON handling to the host. Its JSON envelope is `{message:string, code:string, context:object}`. Request locale is host-owned; diagnostics/previous exceptions are not public copy. Event schemas and source connections are documented in [events](docs/events.md).

The table lists enum discriminators, including any successful codes retained for compatibility. A code is not itself an HTTP status; the throwing exception's `suggestedStatus()` is authoritative, especially legacy/custom constructors. Empty context renders as `{}`; only documented JSON-safe context is presented.

| Code | Suggested status | Public context | Translation key |
| --- | --- | --- | --- |
| `operation_failed` | Exception-defined; see `suggestedStatus()` | Declared safe scalar/array map; otherwise `{}` | `nvl-primitives::responsecode.operation_failed` |
| `exchange_rate_stale` | 409 | Declared safe scalar/array map; otherwise `{}` | `nvl-primitives::responsecode.exchange_rate_stale` |
| `exchange_rate_unavailable` | 422 | Declared safe scalar/array map; otherwise `{}` | `nvl-primitives::responsecode.exchange_rate_unavailable` |


## License

Released under the [MIT License](LICENSE).
