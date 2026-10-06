---
name: nvl-primitives
description: Implement, integrate, test, or review nvl/primitives application value objects in Laravel 13. Use for exact money and exchange rates, Eloquent value-object casts, email/URL/phone/IBAN validation, ISO country/currency/locale codes, coordinates, percentages, length, weight, postal addresses, date-time instants, reference selectors, and Spatie Data/TypeScript boundaries.
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

Use `ValidPrimitive` for scalar primitives and `ValidPhoneNumber` when a national-number region is required. Construct the value object again at the DTO/domain boundary; request validation alone is not a domain invariant.

## Use reference catalogs

Use `ReferenceCatalog` for current ISO countries, currencies, languages, and configured application locales. Cities and banks are deployment-specific: configure small lists or bind an application-owned catalog. Do not copy global city or bank datasets into this package.

## Expose API data

Use `MoneyData`, `LengthData`, `CoordinatesData`, `PostalAddressData`, and `ReferenceOption` for stable Spatie Data and generated TypeScript contracts. Do not expose vendor library objects in JSON responses.

## Verify

Test invalid construction, canonical storage, equality, Eloquent round trips, precision and rounding, currency mismatch, missing exchange rates, region-specific phones, current IBAN metadata, locale normalization, and TypeScript DTO shapes. Run the package Pest suite, Pint, PHPStan at maximum strictness, and the dependency audit.

## Configurable-tenancy release discipline

- Preserve disabled compatibility and package independence; tenant support never creates an undeclared Auth or Suite dependency.
- Use registered package-owned resources, adoption adapters, Actions, and lifecycle APIs. Never add a generic tenant delete-all path or raw cross-package cleanup.
- Treat mapping/configuration hashes, interruption checkpoints, conservation evidence, worker context, tenant-leading queries, and standalone consumption as release contracts.

## Canonical configuration ownership

- Read/write `nvl-primitives` configuration and publish only canonical `nvl-<package>-<resource>` tags. Keep logical package/tenant resource identifiers unchanged.
- Generic config roots and unprefixed package environment names are foreign by default. For an upgrading NVL host only, select `nvl-core.compatibility.legacy_config` package IDs and `legacy_env` explicitly; both default off. Canonical presence wins, including false/null/empty values. Legacy inputs are read without writing back and are removed in major 6.
- Use canonical `NVL_<PACKAGE>_*` variables only in config evaluation, then rebuild configuration caches and restart workers after cutover. Shared Laravel environment variables retain their names. Consult Core's versioned `support/resources/global-names.json` for all renames.
- Old global aliases and legacy route families require separate explicit `global_aliases`/`legacy_routes` package selections. Preserve collisions and use Doctor diagnostics; never grant generic permissions automatically or claim signed-link compatibility without the same authorization/signature checks.

## Application workflow substitution

ExchangeRateProvider already retains host bindings through bindIf. Immutable value construction and calculations remain direct; no new interface or factory is introduced.

Use the existing `ExchangeRateProvider` for exchange-rate substitution; pure APIs remain direct.

Inject the supported contract into host orchestration and bind a native interface
mock or host implementation before resolving that orchestration. Keep concrete
constructors and native workflow bodies intact; internal chains remain package-owned.
Use declared DTOs or unsaved model identity handles for orchestration fixtures.
Use real package workflows and Laravel framework fakes for persistence, tenant,
queue, file, and external-effect integration checks. A host substitute proves
only the host call and result. Keep public declarations tagged `@api` and
constructor/configuration/private helpers internal.

Consult the owning README's Testing your app section for native examples. Include
`vendor/nvl/core/support/consumer-audit.neon` in host PHPStan and declare explicit
`nvlConsumer.testPaths`; the Suite workbench is not consumer tooling.


## Consumer runtime and testing contracts

Start with the package README Quickstart and Testing your app sections. Use `nvl:install <package>` for loaded-package common config publication; it does not enable features, run schema or refresh caches. Preserve native host owner keys/morph maps and selected auth/tenancy defaults. Read full runtime defaults and publish advanced config only deliberately.

Inject the supported focused interfaces and preserve host bindings. Returned model handles do not permit package-table queries/writes outside documented capability/extension seams. Host tests may substitute contracts in Laravel's container, use shipped model factories (ordinary make may persist parents; withoutParents()->make is detached), and use Laravel effect fakes deliberately. Only Media/Stripe have dedicated provider/library fakes; do not invent a universal package fake. Settings InteractsWithSettings is definition-only. Host PHPStan may include vendor/nvl/core/support/consumer-audit.neon; no unpublished workbench command is a consumer requirement.

Read docs/events.md and the package README error table. Domain events use schemaVersion=1, model-free facts and actual source-connection commit callbacks; only six declared old Event suffix aliases remain for major 5. Migrate exact listeners/fakes and suffix wildcards, drain old queued payloads, rebuild event cache and restart workers. Delivery is not a durable outbox. The Core exception renderer is opt-in, JSON-only for respondable failures, with exactly message/code/context and host-selected locale. Do not expose diagnostics or reinterpret missing bindings as authorization denial.

Core package logging uses nvl/normal with CSV quiet by default, stable message keys and bounded context; incidents survive quiet. Do not mutate global logger context or log raw row/provider/content/credential payloads. Run only authorized project checks and report new acceptance as pending until actual output exists.
