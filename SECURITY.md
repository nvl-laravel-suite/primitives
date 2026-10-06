# Security Policy

Submit reports through [this package's private vulnerability reporting form](https://github.com/nvl-laravel-suite/primitives/security/advisories/new).

Security fixes are provided for the prepared `5.x` release line on PHP 8.3+ and Laravel 13.

Report vulnerabilities privately through the repository host's security-advisory feature. Include the primitive, input, canonical output, locale or currency, operation, and impact without including personal identifiers.

Do not use floating-point values for money. Validate untrusted input before construction, treat canonicalization changes as compatibility changes, and keep live exchange-rate credentials outside this package.
