# Install and publish: nvl/primitives

Targets PHP 8.4+ and Laravel 13. Install the selected NVL dependency closure on compatible 7.x versions.

```sh
composer require nvl/primitives:^7.0
```

## Provider discovery

Composer package discovery registers these package-owned providers:

- `Nvl\Primitives\Providers\PrimitivesServiceProvider`

Do not publish or copy vendor providers into the application. If discovery is deliberately disabled, register these exact classes in the application’s `bootstrap/providers.php`. Host policy and extension bindings belong in an application provider; see [usage](usage.md) for which bindings are required.

## Skills

Laravel Boost discovers this folder at `resources/boost/skills/` in the installed Composer package. In the consumer application, run:

```sh
php artisan boost:install --skills --no-interaction
```

Select skills through the installed Boost configuration when setting it up interactively. `php artisan boost:update --no-interaction` refreshes selected resources. Boost is a consumer development tool, not a required runtime dependency of this package. Agent Skills folders can also be copied to the skill directory supported by another agent; copy the entire folder so references and any scripts remain available.

The package also exposes its native Laravel publish group:

```sh
php artisan vendor:publish --tag=nvl-primitives-skills --no-interaction
```

That group copies the full skill tree to `.agents/skills`. Avoid force-overwriting application-authored changes; keep local conventions separate from vendor guidance.

## Publishable groups

- `nvl-primitives-config`
- `nvl-primitives-skills`
- `nvl-primitives-translations`

## Configuration

```sh
php artisan vendor:publish --tag=nvl-primitives-config --no-interaction
```

The consumer owns `config/nvl-primitives.php`. Inspect the actual files registered by this package's config publish group; published sections differ between packages. Set deployment configuration before provider boot when it selects a model, registry or integration. Never store closures in cacheable configuration; bind behavior in an application provider. Rebuild configuration caches and restart long-lived workers after reviewed changes.

## Migrations

This package publishes no database migrations. Host models or schemas required by a capability are application-owned; follow the declarations and constraints in [usage](usage.md).

## Artisan operations

This package declares no Artisan commands. Do not invent package install, doctor, schema or DTO-generation commands.

## Optional packages

Installing an optional library does not grant a policy decision or automatically enable an integration. The manifest suggests:

- `ext-intl`: Enables locale-aware monetary formatting through Brick Money.
- `giggsey/libphonenumber-for-php`: Enables phone number validation and formatting (^9.0).
- `jschaedl/iban-validation`: Enables IBAN validation and formatting (^2.7).
- `symfony/intl`: Enables ISO country, currency, language, and locale reference data (^7.0 || ^8.0).
- `symfony/polyfill-intl-icu`: Enables Symfony Intl on PHP without ext-intl (^1.33).

## Verify the consumer

Use the package-specific checks in [usage](usage.md), verify required host bindings and run the affected consumer tests. Publishing files is not application or production certification. Preserve the documented explicit actor, revision, owner, locale and outer-commit requirements when adapting HTTP, CLI or jobs.

Official framework guidance: [Laravel package development](https://laravel.com/docs/13.x/packages) and [Boost third-party package skills](https://laravel.com/docs/13.x/boost#third-party-package-skills).
