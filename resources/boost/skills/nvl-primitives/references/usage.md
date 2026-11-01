# Primitive consumer recipes

Use [installation](installation.md) for optional feature dependencies and [API](api.md) for factories, cast arguments and output shapes.

## Exact money and explicit rounding

```php
use Brick\Math\RoundingMode;
use Nvl\Primitives\ValueObjects\Money;

$price = Money::of('19.99', 'EUR');
$shipping = Money::minor(500, 'EUR');
$total = $price->add($shipping);
$share = $total->divide(3, RoundingMode::HalfUp);
$amount = $total->amount();        // '24.99'
$minor = $total->minorAmount();    // '2499'
$stored = $total->toArray();       // minor/currency storage representation
```

Factories accept strings/integers and an explicit `CurrencyCode|string`. Minor units must be integral. Construction and arithmetic default to exact representation; supply a rounding mode only where domain policy authorizes losing fractional precision. Adding/subtracting/comparing mismatched currencies fails instead of converting. `allocate($ratios)` and `split($parts)` distribute minor units without losing the total; choose the business allocation ratios in the host.

## Exchange rates and display

```php
use Brick\Math\RoundingMode;
use Nvl\Primitives\Services\CurrencyConverter;
use Nvl\Primitives\ValueObjects\Money;

config(['nvl-primitives.exchange_rates.rates' => ['EUR/USD' => '1.10']]);
$usd = app(CurrencyConverter::class)->convert(
    Money::of('10.00', 'EUR'),
    'USD',
    RoundingMode::HalfUp,
);
```

Configuration is trusted host deployment data, never a conversion request payload. Each directed pair maps one source major unit to target major units; no inverse is inferred. A freshness-aware rate may be `['rate' => '1.10', 'as_of' => '2026-01-01T00:00:00Z', 'max_age_seconds' => 3600]`; `as_of` and age must appear together. `convert` accepts an optional effective `DateTimeInterface` and passes it to the provider. Missing and expired rates raise `ExchangeRateUnavailable` and `ExchangeRateStale`.

Bind `Nvl\Primitives\Contracts\ExchangeRateProvider` in the host, or configure `exchange_rates.implementation`, for a database/live source. Its `rate(CurrencyCode $from, CurrencyCode $to, ?DateTimeInterface $at = null): string` returns an exact positive decimal rate. Keep remote I/O, authorization, caching and durable accounting provenance in that host adapter. `MoneyFormatter::format($money, $locale)` uses explicit display locale or `nvl-primitives.money.default_locale`; display text is not storage.

## Eloquent casts and nulls

Declare these methods inside an application-owned Eloquent model with appropriately typed columns:

```php
protected function casts(): array
{
    return [
        'email' => \Nvl\Primitives\ValueObjects\EmailAddress::class,
        'coordinates' => \Nvl\Primitives\ValueObjects\Coordinates::class,
        'price' => \Nvl\Primitives\ValueObjects\Money::class,
    ];
}
```

Money's default JSON representation is `{"minor":"2499","currency":"EUR"}`. Fixed-currency columns may use `Money::class.':minor,EUR'` or `Money::class.':decimal,EUR'`; an explicit `:json,EUR` enforces that currency on both assignment and hydration. Variable-currency money needs JSON or host-owned amount/currency columns, never a guessed default currency.

Null attributes round-trip as null. A host partial update must preserve omitted fields and explicitly assign null only to a nullable column. Scalar assignment and `ValidPrimitive` accept exact strings/integers, rejecting booleans/floats before coercion. Scalar hydration can accept native database floats where the value supports them, but choose string storage for exact ratios/weights. Array casts require their declared array shape. These casts do not authorize writes or create package-managed models.

## Contact, identity, code and instant values

```php
use Nvl\Primitives\ValueObjects\DateTimeValue;
use Nvl\Primitives\ValueObjects\EmailAddress;
use Nvl\Primitives\ValueObjects\Identifier;
use Nvl\Primitives\ValueObjects\LocaleCode;
use Nvl\Primitives\ValueObjects\TimezoneId;

$email = EmailAddress::from('Reader@Example.COM');
$id = Identifier::from('invoice-42');
$locale = LocaleCode::from('zh_hans_cn');
$timezone = TimezoneId::from('Europe/Sofia');
$instant = DateTimeValue::from('2026-10-25T12:00:00+03:00');
$canonical = $instant->storageValue();
```

Email canonicalization lowercases the domain and preserves quoted local parts. `Identifier` is a nonempty scalar identity value, not proof a database row exists. `TimezoneId` validates a PHP timezone identifier. `DateTimeValue` represents an instant with strict offset validation and UTC canonical storage; `inTimezone()` returns a Carbon immutable presentation value. Locale codes normalize BCP 47-style casing; `CountryCode`/`CurrencyCode` validate standards-backed codes through their feature libraries.

`PhoneNumber::fromRegion('044 668 18 00', 'CH')` supports national input; `PhoneNumber::from('+41446681800')` supports international input. Configure `phone.default_region` only as an explicit host policy. Store `storageValue()` as E.164. `Iban::from('DE89 3704 0044 0532 0130 00')` stores electronic uppercase form; use `formatted`/`masked` for display. `Url` validates a URL at construction. Syntax validation does not authorize outbound requests or contact delivery.

## Quantities and structured addresses

```php
use Brick\Math\RoundingMode;
use Nvl\Primitives\ValueObjects\Coordinates;
use Nvl\Primitives\ValueObjects\Length;
use Nvl\Primitives\ValueObjects\Percentage;
use Nvl\Primitives\ValueObjects\Weight;

$discount = Percentage::fromPercent(20)->of('125.00');
$centimetres = Length::from('12', 'in')->in('cm', 2, RoundingMode::Unnecessary);
$grams = Weight::kilograms('1.5')->inGrams();
$point = Coordinates::from('42.6977', '23.3219');
```

Percentages store decimal ratios; `fromPercent` differs deliberately from `fromDecimal`. Decimal quantities use strings/integers and explicit rounding for conversion to a target scale. Coordinates admit finite latitude/longitude within geographic bounds; their array form rejects booleans. `PostalAddress` requires line1, locality and `CountryCode`; line2, administrative area and postal code can be null. Use its native constructor or the exact `fromArray` contract in API rather than passing an arbitrary address DTO.

## Validation and catalogs

For an email request rule use `new \Nvl\Primitives\Rules\ValidPrimitive(EmailAddress::class)`. For national phone validation use `ValidPhoneNumber` with the desired region. These are Laravel validation rules, not a replacement for domain construction. Catch `InvalidPrimitive` at the host boundary and preserve useful field context in the application's response.

`ReferenceCatalog::countries($search, $locale, $limit)`, `currencies`, `languages` and `locales` return bounded lists with `code`, `label` and scalar `metadata`. ISO data comes from Symfony Intl; application locales come from Core's `LocaleCatalog`. `cities`/`banks` use `nvl-primitives.reference` entries or a host-owned source; the package does not invent a universal city/bank database. Check the API limit contract instead of returning an unbounded select list.

## Verification and absence

Test money exactness/rounding, wrong currencies, missing/stale rates, canonical storage, null cast round trips, contact normalization and model input rejection. Test the actual phone/IBAN/ISO library for enabled features and boot independently when those optional libraries are absent. No actor, revision, transaction, recovery or Ownership service is implied by constructing a value; host domain operations supply those guarantees. Primitives adds no maintenance Artisan command or database schema.
