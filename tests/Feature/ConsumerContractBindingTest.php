<?php

declare(strict_types=1);

use Illuminate\Config\Repository;
use Illuminate\Container\Container;
use Illuminate\Filesystem\FilesystemServiceProvider;
use Illuminate\Foundation\Application;
use Nvl\Primitives\Contracts\ExchangeRateProvider;
use Nvl\Primitives\Providers\PrimitivesServiceProvider;
use Nvl\Primitives\Services\ConfiguredExchangeRateProvider;
use Nvl\Primitives\Tests\TestCase;

if (! in_array(dirname(__DIR__).'/Pest.php', get_included_files(), true)) {
    uses(TestCase::class);
}

test('Primitives retains its native exchange rate default and accepts a late host adapter', function (): void {
    expect($this->app->make(ExchangeRateProvider::class))->toBeInstanceOf(ConfiguredExchangeRateProvider::class);
    $host = Mockery::mock(ExchangeRateProvider::class);
    $this->app->instance(ExchangeRateProvider::class, $host);
    expect($this->app->make(ExchangeRateProvider::class))->toBe($host);
});

test('Primitives preserves an early host exchange rate adapter through provider registration', function (): void {
    $consumer = new Application($this->app->basePath());
    $consumer->instance('config', new Repository($this->app->make('config')->all()));
    $consumer->instance('env', 'testing');
    $consumer->register(FilesystemServiceProvider::class);
    $host = Mockery::mock(ExchangeRateProvider::class);
    $consumer->instance(ExchangeRateProvider::class, $host);
    try {
        $consumer->register(PrimitivesServiceProvider::class);
        expect($consumer->make(ExchangeRateProvider::class))->toBe($host);
    } finally {
        Container::setInstance($this->app);
        $consumer->flush();
    }
});
