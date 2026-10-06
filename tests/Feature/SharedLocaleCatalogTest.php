<?php

declare(strict_types=1);

use Illuminate\Config\Repository;
use Nvl\Primitives\Services\ReferenceCatalog;
use Nvl\Support\Contracts\LocaleCatalog;
use Nvl\Support\Locales\ApplicationLocaleCatalog;

it('uses the shared catalog instead of a conflicting legacy primitives catalog', function (): void {
    config(['primitives.locales.supported' => ['en']]);
    $catalog = new ApplicationLocaleCatalog(new Repository(['app' => ['locale' => 'fr', 'fallback_locale' => 'de']]));
    app()->instance(LocaleCatalog::class, $catalog);

    expect(array_column(app(ReferenceCatalog::class)->locales(displayLocale: 'en'), 'code'))->toBe(['fr', 'de']);
});

it('derives standalone primitive locale options from application locales', function (): void {
    config([
        'app.locale' => 'BG_bg',
        'app.fallback_locale' => 'en',
        'primitives.locales.supported' => null,
    ]);

    expect(array_column(app(ReferenceCatalog::class)->locales(displayLocale: 'en'), 'code'))->toBe(['bg-BG', 'en']);
});
