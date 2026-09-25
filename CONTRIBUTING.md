# Contributing to NVL Primitives

This public repository is a publication mirror of private source. Open an issue
here for a bug or proposal; include a reproduction and, if helpful, a patch.
Maintainers apply accepted changes in source and publish a mirror release.
Direct mirror pull requests do not update source. See the
[organization contribution guide](https://github.com/nvl-laravel-suite/.github/blob/main/CONTRIBUTING.md).

Changes must preserve immutability, canonical construction, equality, serialization, validation, and Laravel cast behavior.

Add exhaustive datasets or property-style tests for valid and invalid inputs, precision, overflow, rounding, allocation, localization, and cast round trips. Run Pest, Pint, PHPStan at maximum strictness, Composer validation, dependency analysis, and distribution validation.

New values must be application-neutral and must not introduce persistence or external API access.
