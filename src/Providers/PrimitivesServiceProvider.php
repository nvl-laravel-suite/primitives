<?php

declare(strict_types=1);

namespace Nvl\Primitives\Providers;

use Illuminate\Support\ServiceProvider;
use InvalidArgumentException;
use Nvl\Data\Services\TypeScriptSourceRegistry;
use Nvl\Primitives\Contracts\ExchangeRateProvider;
use Nvl\Primitives\Services\ConfiguredExchangeRateProvider;
use Nvl\Support\Globals\GlobalNames;
use Nvl\Support\Traits\MergesPackageConfiguration;
use Nvl\Support\Traits\RegistersNamespacedResources;

/**
 * Registers primitive configuration, contracts, type sources, and package resources.
 */
final class PrimitivesServiceProvider extends ServiceProvider
{
    use MergesPackageConfiguration;
    use RegistersNamespacedResources;

    /**
     * Register package configuration and default boundary implementations.
     */
    public function register(): void
    {
        $this->mergePackageConfiguration(
            __DIR__.'/../../config/nvl-primitives.php',
            'primitives',
        );

        $implementation = config(
            'nvl-primitives.exchange_rates.implementation',
            ConfiguredExchangeRateProvider::class,
        );

        if (
            ! is_string($implementation)
            || ! is_a($implementation, ExchangeRateProvider::class, true)
        ) {
            throw new InvalidArgumentException(
                'primitives.exchange_rates.implementation must implement ExchangeRateProvider.',
            );
        }

        $this->app->bindIf(ExchangeRateProvider::class, $implementation);
    }

    /**
     * Publish configuration and agent guidance, and register generated types.
     */
    public function boot(TypeScriptSourceRegistry $typeScriptSources): void
    {
        $typeScriptSources->register(__DIR__.'/..', 'nvl/primitives');
        $this->app->make(GlobalNames::class)->translations('primitives', __DIR__.'/../../lang', $this->app->make('translation.loader'));

        $this->publishes([
            __DIR__.'/../../config/nvl-primitives.php' => config_path('nvl-primitives.php'),
        ], 'primitives-config');

        $this->publishes([
            __DIR__.'/../../lang' => lang_path('vendor/nvl-primitives'),
        ], 'primitives-translations');

        $this->publishes([
            __DIR__.'/../../resources/boost/skills' => base_path('.agents/skills'),
        ], 'primitives-skills');
    }
}
