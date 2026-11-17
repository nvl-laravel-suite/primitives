# Changelog


All notable changes to `nvl/primitives` are documented here.

## [Unreleased]

## [7.4.0] - 2026-11-17

### Fixed

- Money casts compare stored values by money rather than by raw text, so reassigning an equal amount keeps the model clean when PostgreSQL `jsonb` reformats the stored JSON, its keys are reordered, or a decimal column returns `10.5` for `10.50`.
- Structured primitive casts (`ArrayPrimitiveCast`, for example `Coordinates` and `PostalAddress`) compare stored JSON by primitive equality, so `jsonb` spacing and key order no longer mark an unchanged attribute dirty.

<!-- NVL GENERATED CHANGES START -->
### Changed

- consumer-audit fixes across the suite, UTC timestamps, core/forms release ordering
<!-- NVL GENERATED CHANGES END -->

## [7.3.0] - 2026-11-17

### Added

- `MoneyCast` accepts a fifth `strict` argument (`Money::class.':decimal,EUR,price_minor,,strict'`) that makes the minor column the only source: it never falls back to the decimal, throws when the minor column is not loaded beside a loaded decimal, and rejects a null minor column beside a decimal. An empty column argument now means the default column.
- `Identifier` and `TimezoneId` are tagged `@api` and listed in the consumer API catalog; they were already documented and stable.

### Fixed

- `DateTimeCast` implements `ComparesCastableAttributes`, so reassigning an equal instant no longer marks the model dirty when PostgreSQL hydrates a different textual form (`+00` versus `.000000+00:00`, a non-UTC session offset or unpadded fractions). Null is equal only to null.

<!-- NVL GENERATED CHANGES START -->
### Changed

- implement the Final spec audit for one release
<!-- NVL GENERATED CHANGES END -->

## [7.2.0] - 2026-11-16

### Added

- Opt into numbers with `new PresentationOptions(numeric: true)`. Unsafe integers throw. Strings stay the default.
- Added optional `ReferenceCodeDirectory`; existing `ReferenceDirectory` implementations need no change.

### Changed

- Arithmetic now carries compatible source amounts. Totals that mix source currencies drop provenance. Pass a rounding mode when either currency needs one.
- `MoneyPresenter` output for converted arithmetic results now includes `recordCurrency` and `recordCents`.

<!-- NVL GENERATED CHANGES START -->
### Changed

- adoption flow, PostgreSQL proof, release health and 7.2 floors
<!-- NVL GENERATED CHANGES END -->

## [7.1.0] - 2026-11-16

### Highlights

- Resolve currency from the record for money casts and support synchronized decimal and minor-unit columns.

<!-- NVL GENERATED CHANGES START -->
### Changed

- close NVL consumer adoption and portability gaps
- Merge pull request #21 from nicolasvlachos/codex/consumer-postgresql-validation
<!-- NVL GENERATED CHANGES END -->

## [7.0.2] - 2026-11-13

### Fixed

- Preserve explicit `null` attributes in `MoneyCast` and harden configured exchange-rate validation and optional-library probing.

<!-- NVL GENERATED CHANGES START -->
### Changed

- harden package runtime safety, PostgreSQL portability and test performance
<!-- NVL GENERATED CHANGES END -->

## [7.0.1] - 2026-11-11

### Fixed

- `LocaleCode` no longer accepts a subtag that ends in a line break (`"es-419\n-valencia"` was stored with the embedded newline).
- `Money`, `Percentage`, `Weight`, `Length` and `Coordinates` reject decimal input with more than three exponent digits; `1e-999999999` previously exhausted memory with an uncatchable fatal error instead of failing validation.
- `Percentage::percentRounded()` reports an inexact result under `RoundingMode::Unnecessary` as `InvalidPrimitive`, like the other rounded operations.
- `ReferenceCatalog::cities()` and `banks()` accept configured entries whose codes are numeric (PHP stores such array keys as integers, so they were refused as "not a string"); a list without codes is still rejected.
- Documentation: the translation publish path is `lang/vendor/nvl-primitives`, and the security policy states the declared Laravel `^13.0` range.

<!-- NVL GENERATED CHANGES START -->
### Changed

- harden decimal input and value objects
<!-- NVL GENERATED CHANGES END -->

## [7.0.0] - 2026-11-03

- Adopt the reviewed NVL 7 family for PHP 8.4+ and Laravel 13, with compatible required and optional NVL peers. Review the package upgrade guide before deployment.

### Changed

- Align NVL peers and consumer contracts on the coordinated 7.x line. Review [UPGRADING.md](UPGRADING.md) for adoption and application-owned integration requirements.

## [6.0.0] - 2026-10-20

### Changed

- Remain independent of Auth, Ownership and Tenancy while aligning the shared Core dependency with the coordinated major.
- Align required/development NVL peers on `^6.0`, optional peer conflicts on major 6, and `dev-main` on `6.x-dev`. Review [UPGRADING.md](UPGRADING.md) before rollout.

## [5.0.1] - 2026-10-08

### Changed

- Correct published-family verification and adoption guidance for local Dagger CI; runtime contracts are unchanged.


## [5.0.0] - 2026-10-08

### Changed

- ExchangeRateProvider already retains host bindings through bindIf. Immutable value construction and calculations remain direct; no new interface or factory is introduced. Document contract substitution and truthful host fixtures in Testing your app.
- Classify the supported consumer PHP surface with explicit source annotations and restrict package model handles to declared identity and in-memory read fields; preserve existing workflow behavior and concrete signatures.
- Adopt lockstep major 5 with required and development NVL peer floors of `^5.0`.
- Keep value-object behavior unchanged; adopt canonical configuration, translations and the lockstep major.
- Review [UPGRADING.md](UPGRADING.md) before adopting the new names and infrastructure boundaries.

## [2.2.1] - 2026-09-26

### Documentation

- Clarify public support, contribution, and private security reporting paths.

## [2.2.0] - 2026-09-25

### Changed

- Prepare `nvl/primitives` for independent Composer and Git publication; require `nvl/core` for shared Support and Data services.

## [2.1.0] - 2026-09-23

### Fixed

- Preserve a host application's exchange-rate provider binding when the
  Primitives service provider registers.

### Changed

- Create the test fixture schema once per refreshed database and roll back
  fixture rows between tests.

## [2.0.1] - 2026-09-22

### Fixed

- Preserve quoted email local parts containing `@` while normalizing only the domain.
- Reject out-of-range RFC 3339 offset hours and minutes with `InvalidPrimitive`.
- Reject boolean coordinates instead of coercing them to numeric values.
- Enforce an explicitly configured currency when hydrating JSON money casts.

## [2.0.0] - 2026-08-29

### Changed

- Kept value objects, casts, validation rules, and reference catalogs as the
  package's model-free Suite 2.0 consumer boundary.

## [1.0.7] - 2026-08-22

### Changed

- Aligned the documented runtime requirement with the PHP 8.4+ package
  baseline.

## [1.0.5] - 2026-08-12

### Changed

- Released unchanged under the suite's shared version.

## [1.0.2] - 2026-08-12

- Fixed fixed-currency decimal casts for SQLite `DECIMAL` hydration while continuing to reject floating-point input.
- Removed tests and static-analysis configuration from release archives.
- Added the `primitives-translations` publish tag for the bundled English and
  Bulgarian validation catalogs.

## [1.0.0] - 2026-08-08

- Added immutable validated value objects, Laravel casts, validation rules, and DTOs.
- Added exact money arithmetic, allocation, explicit rounding and currency contracts, directed conversion rates, deterministic formatting, and canonical minor-unit storage.
- Added strict RFC 3339 UTC instants, ordered BCP 47 core locale parsing, explicit `Length`, nullable postal codes, and complete weight conversions.
- Added strict null semantics and scalar fixed-currency modes to Eloquent casts.
- Added English and Bulgarian Laravel validation messages.
- Added ISO-backed reference catalogs with injected configuration, distinct locale labels, strict entries and deterministic unavailable or stale exchange-rate behavior.
- Added stable `Nvl.Primitives.*` TypeScript-facing contracts.
