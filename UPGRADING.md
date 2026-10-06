# Upgrading NVL Primitives

## Consumer contracts, committed events and runtime policy (5.x)

Prefer focused public interfaces in constructor injection; native implementations remain container defaults and host prebindings win. Returned models are documented identity/data handles: use package contracts for reads/writes and capability-specific batch readers instead of direct package queries. Enable the shipped Core PHPStan include in your host; do not invoke the suite workbench static audit command in a consumer.

Events now carry immutable schemaVersion=1 and scalar/DTO snapshots. Replace model-bearing event fields with the IDs listed in [events](docs/events.md); load only through an authorized public reader when needed. Only six declared legacy `*Event` names are retained as PHP aliases for major 5, removal no earlier than major 6. Migrate exact imports/listeners/fakes to canonical names, replace suffix wildcard patterns explicitly, drain old queued payloads, rebuild event caches and restart workers. Framework Verified/PasswordReset remain native classes. Source-connection callbacks are process-local after-commit publication, not a durable outbox or exactly-once delivery.

Package failures have a marker and optional response metadata. Opt into Core's JSON renderer deliberately; preserve existing host handlers and request-locale selection. Missing required host adapters produce `binding_required`/500; genuine configured authorization denial retains native handling. See the README error table and required-bindings section where applicable.

Factories ship in runtime package mappings for host tests. Ordinary make may persist parents; withoutParents()->make creates detached fixtures. Supply persisted native owners/parents and active tenants explicitly, retain source revisions, and never treat a factory row as a real storage/provider/workflow effect. Core's optional installer publishes common config without enabling features; strict Doctor and explicit deployment cache/worker steps belong in the host release process. C3/C4/E executable acceptance is pending until recorded by integration.


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

`nvl-primitives.locales` is deprecated for one major cycle. `ReferenceCatalog::locales()` now reads `Nvl\Support\Contracts\LocaleCatalog`. Configure `nvl-core.locales` or bind the contract; standalone Core/Primitives usage requires no Translatable installation. Without an explicit catalog, supported options derive from valid application locale and fallback values.

An explicit legacy `nvl-primitives.locales.supported` list is translated only when the standalone default has no canonical selection. Translatable's adapter or a host-bound catalog takes precedence. Run `php artisan nvl:doctor` to find deprecated configuration and conflicts before removing the old list.

Primitives retains its stricter language/script/region/variant validation and uses Core's shared separator and casing normalization. No stored locale values are rewritten. Rebuild configuration caches and restart workers after configuration changes.

## Tagged consumer PHP boundary

Use source `@api` workflows, extension contracts, and value types for application integration. Direct use of untagged implementations or `@internal` members is unsupported. This classification keeps existing concrete Action signatures and runtime behavior; it does not authorize package model persistence, ad hoc queries, relation traversal, or generic model serialization. Returned models are identity/result handles with only the explicitly declared in-memory read fields described in the README.

## Application workflow contracts

ExchangeRateProvider already retains host bindings through bindIf. Immutable value construction and calculations remain direct; no new interface or factory is introduced.

Use the existing `ExchangeRateProvider` for exchange-rate substitution; pure APIs remain direct.

Inject these contracts when application workflows need substitution. Native
concrete constructors and operation signatures remain available through major 5;
internal workflow chains are unchanged. Register host implementations before
package discovery or replace the contract before resolving a new host service.
See [Testing your app](README.md#testing-your-app) for native fixtures and the
shipped consumer-audit PHPStan configuration.
