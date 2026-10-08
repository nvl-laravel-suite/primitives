# Security Policy

Submit reports through [this package's private vulnerability reporting form](https://github.com/nvl-laravel-suite/primitives/security/advisories/new).

Security fixes are provided for the published `5.x` release line. Composer declares PHP `^8.4` and Laravel `^12.0|^13.0`. The local Dagger release gate verifies PHP 8.4/Laravel 13 with MySQL 8.4 and PostgreSQL 17 persistence contracts; PHP 8.5, Laravel 12 and MariaDB require separate compatibility evidence. Upstream security lifecycle limits still apply.

Report vulnerabilities privately through the repository host's security-advisory feature. Include the primitive, input, canonical output, locale or currency, operation, and impact without including personal identifiers.

Do not use floating-point values for money. Validate untrusted input before construction, treat canonicalization changes as compatibility changes, and keep live exchange-rate credentials outside this package.
