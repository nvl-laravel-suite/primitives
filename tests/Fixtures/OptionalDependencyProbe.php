<?php

declare(strict_types=1);

use Composer\Autoload\ClassLoader;
use Iban\Validation\Validator;
use Illuminate\Config\Repository;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Events\EventServiceProvider;
use Illuminate\Filesystem\FilesystemServiceProvider;
use Illuminate\Foundation\Application;
use Illuminate\Translation\TranslationServiceProvider;
use libphonenumber\PhoneNumberUtil;
use Nvl\Primitives\Casts\MoneyCast;
use Nvl\Primitives\Contracts\ExchangeRateProvider;
use Nvl\Primitives\Providers\PrimitivesServiceProvider;
use Nvl\Primitives\Services\ReferenceCatalog;
use Nvl\Primitives\ValueObjects\CountryCode;
use Nvl\Primitives\ValueObjects\Iban;
use Nvl\Primitives\ValueObjects\Money;
use Nvl\Primitives\ValueObjects\PhoneNumber;
use Nvl\Support\Providers\LocaleServiceProvider;
use Nvl\Support\Providers\SupportServiceProvider;
use Symfony\Component\Intl\Countries;

[$script, $profile, $vendor, $applicationPath] = $argv;

require $vendor.'/composer/ClassLoader.php';

$blockedPackages = [
    '/giggsey/libphonenumber-for-php',
    '/jschaedl/iban-validation',
    '/symfony/polyfill-intl-icu',
];

if ($profile === 'without-optional') {
    $blockedPackages[] = '/symfony/intl';
} elseif ($profile !== 'without-icu') {
    throw new InvalidArgumentException('Unknown optional dependency profile.');
}

$isAvailable = static function (string $path) use ($blockedPackages): bool {
    foreach ($blockedPackages as $package) {
        if (str_contains(str_replace('\\', '/', $path), $package)) {
            return false;
        }
    }

    return true;
};

$loader = new ClassLoader;

foreach (require $vendor.'/composer/autoload_psr4.php' as $prefix => $paths) {
    $available = array_values(array_filter((array) $paths, $isAvailable));

    if ($available !== []) {
        $loader->addPsr4($prefix, $available);
    }
}

$classMap = array_filter(require $vendor.'/composer/autoload_classmap.php', $isAvailable);
$loader->addClassMap($classMap);
$loader->register(true);

foreach (require $vendor.'/composer/autoload_files.php' as $path) {
    if ($isAvailable($path)) {
        require_once $path;
    }
}

$app = new Application($applicationPath);
$app->instance('config', new Repository(['app' => ['locale' => 'en', 'fallback_locale' => 'en']]));
$app->register(EventServiceProvider::class);
$app->register(FilesystemServiceProvider::class);
$app->register(TranslationServiceProvider::class);
$app->register(SupportServiceProvider::class);
$app->register(LocaleServiceProvider::class);
$app->register(PrimitivesServiceProvider::class);
$app->boot();

$call = static function (callable $operation): array {
    try {
        $operation();

        return ['type' => 'success', 'message' => ''];
    } catch (Throwable $exception) {
        return ['type' => $exception::class, 'message' => $exception->getMessage()];
    }
};

echo json_encode([
    'booted' => $app->make(ExchangeRateProvider::class) !== null,
    'moneyMinor' => Money::of('19.99', 'EUR')->minorAmount(),
    'moneyDecimalFloatCast' => (new MoneyCast('decimal', 'EUR'))
        ->get(new class extends Model {}, 'amount', 19.99, [])
        ?->minorAmount(),
    'nativeIntlLoaded' => extension_loaded('intl'),
    'phoneAvailable' => class_exists(PhoneNumberUtil::class),
    'ibanAvailable' => class_exists(Validator::class),
    'intlAvailable' => class_exists(Countries::class),
    'icuAvailable' => class_exists(Locale::class),
    'phone' => $call(static fn () => PhoneNumber::fromRegion('044 668 18 00', 'CH')),
    'iban' => $call(static fn () => Iban::from('DE89 3704 0044 0532 0130 00')),
    'country' => $call(static fn () => CountryCode::from('BG')),
    'catalog' => $call(static fn () => $app->make(ReferenceCatalog::class)->countries('BG')),
], JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES).PHP_EOL;
