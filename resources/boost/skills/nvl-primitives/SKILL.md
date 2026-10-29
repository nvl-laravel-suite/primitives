---
name: nvl-primitives
description: Implement, integrate, test, or review nvl/primitives application value objects in Laravel 13. Use for exact money and exchange rates, Eloquent value-object casts, email/URL/phone/IBAN validation, ISO country/currency/locale codes, coordinates, percentages, length, weight, postal addresses, date-time instants, and reference catalogs.
---

# NVL Primitives

Treat primitives as immutable validated values, not bags of formatting helpers. Preserve their canonical storage representation at every persistence boundary.

## Choose the primitive

- Use `Money` for monetary values. Never use floats for construction, arithmetic, exchange rates, or storage.
- Use `CurrencyCode`, `CountryCode`, and `LocaleCode` for standards-backed codes.
- Use `PhoneNumber` for E.164 storage and `Iban` for electronic IBAN storage.
- Use `EmailAddress` and `Url` when the value must be syntactically valid at construction.
- Use `Coordinates`, `PostalAddress`, `Percentage`, `Length`, `Weight`, and `DateTimeValue` for their corresponding domain values.
- Create a new primitive only when validation, canonicalization, equality, or operations are meaningfully stronger than the underlying scalar.

## Persist values

Declare the value-object class directly in `casts()`:

```php
protected function casts(): array
{
    return [
        'email' => EmailAddress::class,
        'coordinates' => Coordinates::class,
        'price' => Money::class,
    ];
}
```

`Money::class` stores `{"minor":"…","currency":"…"}`. Use `Money::class.':minor,EUR'` or `Money::class.':decimal,EUR'` only for an explicitly fixed-currency column. Store variable-currency money as JSON or as two application-owned columns; never silently infer record currency.

An explicit `Money::class.':json,EUR'` currency is enforced on assignment and hydration. Coordinate arrays reject booleans, date-time offsets must have valid hours and minutes, and email normalization preserves quoted local parts while lowercasing only the domain.

## Calculate money

- Construct from decimal strings or integer minor units with an explicit currency.
- Require an explicit `RoundingMode` whenever an operation cannot be represented exactly.
- Convert through injected `CurrencyConverter` and `ExchangeRateProvider`.
- Configure every exchange-rate direction explicitly; never derive an inverse rate.
- Format for display through injected `MoneyFormatter`.
- Bind a live or database provider at the application boundary; do not call an external exchange-rate API from a value object.
- Keep the source amount and currency auditable outside the converter when legal or accounting provenance requires them.

## Validate input

Use `ValidPrimitive` for scalar primitives and `ValidPhoneNumber` when a national-number region is required. Construct the value object again at the domain boundary; request validation alone is not a domain invariant.

Scalar cast assignment and `ValidPrimitive` accept exact strings and integers, rejecting booleans and input floats before coercion. Scalar hydration can accept native database floats when the selected primitive supports them; choose string storage for exact decimal ratios or weights.

## Use reference catalogs

Use `ReferenceCatalog` for current ISO countries, currencies, languages, and application locales from Core's `LocaleCatalog`. Its bounded records have `code`, `label`, and scalar `metadata` keys. Cities and banks are deployment-specific: configure small lists or use an application-owned catalog. Install `symfony/intl` before calling ISO catalog and code features.

## Expose application data

Use each value object's canonical scalar or `toArray()` method for application mapping. The consumer owns DTOs, API resources, and frontend types. Do not expose vendor library objects in JSON responses. Phone and IBAN support require `giggsey/libphonenumber-for-php` and `jschaedl/iban-validation` when called; the package boots without them.

## Verify

Test invalid construction, canonical storage, equality, Eloquent round trips, precision and rounding, currency mismatch, missing exchange rates, region-specific phones, current IBAN metadata, locale normalization, and optional-library absence. Run the package Pest suite, Pint, PHPStan at maximum strictness, and the dependency audit.

## Package boundary

Primitives has no package Actions, HTTP controllers, models, migrations, TypeScript generator, or passive DTOs. Host code may inject `ExchangeRateProvider` to supply rates; `CurrencyConverter` retains the host binding. Pure value construction and formatting stay direct. Use Core Support's `LocaleCatalog` for application-supported locales. Keep HTTP validation, response shaping, and frontend contracts in the consumer.

## Testing your app

Test exact arithmetic with strings or integer minor units, round-trip value objects through their native casts, and test invalid inputs at the domain boundary. Substitute only the exchange-rate provider when a host-owned rate source needs isolation. Exercise optional phone, IBAN, and ISO features with their actual libraries installed; test boot independently when they are absent. Run the package Pest tests and its configured maximum-level PHPStan analysis before release.
