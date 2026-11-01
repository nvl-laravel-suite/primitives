---
name: nvl-primitives
description: Use when integrating immutable application values, exact money or exchange rates, Eloquent primitive casts, validated contact/ISO/location values or reference catalogs in Laravel.
---

# NVL Primitives

Primitives supplies validated immutable values and focused conversion/catalog services.
The host owns persistence models, domain workflows, transport projections and authorization.
Construct a primitive when its canonicalization, equality or operations carry domain meaning.

## References

- [Installation](references/installation.md): required and optional libraries, provider and publication details.
- [API](references/api.md): value factories, cast arguments, rules and service signatures.
- [Usage](references/usage.md): exact money, casts, rates, quantities, contacts, dates and catalogs.

Read only the recipe for the feature in use, then check exact factory types in API.

## Choose the value

| Domain | Value/capability |
| --- | --- |
| Amount and currency | `Money`, `CurrencyCode` |
| Application identity/timezone | `Identifier`, `TimezoneId` |
| Validated contact data | `EmailAddress`, `Url`, `PhoneNumber`, `Iban` |
| Standards-backed codes | `CountryCode`, `CurrencyCode`, `LocaleCode` |
| Exact quantities | `Percentage`, `Length`, `Weight` |
| Structured location | `Coordinates`, `PostalAddress` |
| Instant | `DateTimeValue` |
| Rate lookup/conversion/display | `ExchangeRateProvider`, `CurrencyConverter`, `MoneyFormatter` |
| Bounded select/reference data | `ReferenceCatalog` |

## Preserve exact values

Use decimal strings or integer minor units for money; never construct or store it through floats.
Supply currency explicitly and a rounding mode wherever an inexact result is allowed.
Money arithmetic compares currencies rather than silently converting them.
Exchange rates are directed positive decimal strings; configure every required direction explicitly.
The host binds live/database rate acquisition; values do not call remote providers.
Return canonical strings or native `toArray()` projections, not vendor objects.
Keep source amounts and conversion provenance in host records when the domain requires them.

## Persist and validate

Declare the primitive class in the host model's `casts()`.
Money defaults to JSON minor/currency storage; fixed-currency cast modes require deliberate schema choices.
Null model attributes remain null rather than constructing an invalid primitive.
Do not confuse omission in a host mutation with assigning null to clear a nullable column.
Use `ValidPrimitive` only for scalar primitive classes and `ValidPhoneNumber` for region-aware phone input.
Rules validate transport input; construct the value again at the domain boundary.
Scalar assignment rejects booleans and input floats before coercion.
There is no package model substitution/actor/revision/transaction protocol because the package owns no domain rows.

## Optional features and bounds

Phone parsing requires `giggsey/libphonenumber-for-php`; national input needs an explicit/default region.
IBAN features require `jschaedl/iban-validation`; ISO catalogs/codes use `symfony/intl`.
Money display localization uses `ext-intl`; consult installation for actual dependency versions.
The package must boot with optional feature libraries physically absent; calling those features may fail explicitly.
Reference catalog records contain `code`, `label` and scalar metadata.
Application locales come from Core's `LocaleCatalog`; cities and banks are host-configured small lists or a host catalog.
No optional Ownership, Tenancy or Data runtime provides primitive authority.

## Verify the boundary

Test invalid construction, canonical equality, null/cast round trips and currency mismatch.
Test exact arithmetic, explicit rounding, directed missing/stale rates and optional-feature absence.
Keep lookup limits bounded and test actual optional libraries when a feature uses them.
Run affected application tests with `php artisan test --compact`.
No package migration, HTTP surface or maintenance Artisan command is supplied.
