# Upgrading NVL Primitives

## Upgrading to 1.0

Version 1.0 is database-free and contains reusable values only.

1. Replace application-specific Core values with the matching immutable value objects.
2. Construct money from decimal strings or minor units with an explicit currency, never floats.
3. Store variable-currency money canonically as `{"minor":"…","currency":"…"}`; use `MoneyData` when an API also needs the major-unit amount.
4. Select JSON, fixed-currency minor, or fixed-currency decimal storage explicitly for `MoneyCast`.
5. Pass a `RoundingMode` to every currency conversion and configure exchange rates in each required direction.
6. Use `MoneyFormatter` for display instead of formatting through the value object.
7. Use `Length` and `LengthData` for length values; unit conversion requires an explicit scale and rounding mode.
8. Supply timezone-qualified RFC 3339 strings to `DateTimeValue`; its storage, JSON, and string form are canonical UTC with microseconds.
9. Treat `PostalAddress::postalCode` as nullable and preserve that nullability in API and TypeScript contracts.
10. Bind `ExchangeRateProvider` when conversion is used.
11. Keep application catalogs, repositories, orchestration, and database reference tables outside this package.
12. Verify cast round trips before converting existing columns.

Changed canonical serialization is a data migration and must be handled by the consuming application.

## Shared locale catalog cutover

`primitives.locales` is deprecated for one major cycle. `ReferenceCatalog::locales()` now reads `Nvl\Support\Contracts\LocaleCatalog`. Configure `nvl-core.locales` or bind the contract; standalone Core/Primitives usage requires no Translatable installation. Without an explicit catalog, supported options derive from valid application locale and fallback values.

An explicit legacy `primitives.locales.supported` list is translated only when the standalone default has no canonical selection. Translatable's adapter or a host-bound catalog takes precedence. Run `php artisan nvl:doctor` to find deprecated configuration and conflicts before removing the old list.

Primitives retains its stricter language/script/region/variant validation and uses Core's shared separator and casing normalization. No stored locale values are rewritten. Rebuild configuration caches and restart workers after configuration changes.
