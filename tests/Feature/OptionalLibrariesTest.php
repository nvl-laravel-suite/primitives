<?php

declare(strict_types=1);

use Composer\Autoload\ClassLoader;

/**
 * @return array<string, mixed>
 */
function optionalDependencyProbe(string $profile): array
{
    $packageRoot = dirname(__DIR__, 2);
    $vendor = dirname((new ReflectionClass(ClassLoader::class))->getFileName(), 2);
    $process = proc_open(
        [PHP_BINARY, $packageRoot.'/tests/Fixtures/OptionalDependencyProbe.php', $profile, $vendor, $packageRoot],
        [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']],
        $pipes,
        $packageRoot,
    );

    if (! is_resource($process)) {
        throw new RuntimeException('Could not start optional dependency probe.');
    }

    fclose($pipes[0]);
    $output = stream_get_contents($pipes[1]);
    $errors = stream_get_contents($pipes[2]);
    fclose($pipes[1]);
    fclose($pipes[2]);
    $status = proc_close($process);

    if ($status !== 0 || $output === false) {
        throw new RuntimeException("Optional dependency probe failed ({$status}): {$errors}");
    }

    return json_decode($output, true, flags: JSON_THROW_ON_ERROR);
}

it('boots with phone, IBAN, and Intl libraries hidden from the isolated autoloader', function (): void {
    $result = optionalDependencyProbe('without-optional');

    expect($result['booted'])->toBeTrue()
        ->and($result['moneyMinor'])->toBe('1999')
        ->and($result['phoneAvailable'])->toBeFalse()
        ->and($result['ibanAvailable'])->toBeFalse()
        ->and($result['intlAvailable'])->toBeFalse()
        ->and($result['icuAvailable'])->toBeFalse()
        ->and($result['phone'])->toBe([
            'type' => RuntimeException::class,
            'message' => 'Phone number support requires giggsey/libphonenumber-for-php. Install it with composer require giggsey/libphonenumber-for-php.',
        ])
        ->and($result['iban'])->toBe([
            'type' => RuntimeException::class,
            'message' => 'IBAN support requires jschaedl/iban-validation. Install it with composer require jschaedl/iban-validation.',
        ])
        ->and($result['country'])->toBe([
            'type' => RuntimeException::class,
            'message' => 'ISO reference support requires symfony/intl. Install it with composer require symfony/intl.',
        ])
        ->and($result['catalog'])->toBe($result['country']);
});

it('boots with Symfony Intl but reports missing native or polyfilled ICU support', function (): void {
    $result = optionalDependencyProbe('without-icu');

    expect($result['booted'])->toBeTrue()
        ->and($result['moneyMinor'])->toBe('1999')
        ->and($result['intlAvailable'])->toBeTrue()
        ->and($result['icuAvailable'])->toBeFalse()
        ->and($result['country'])->toBe([
            'type' => RuntimeException::class,
            'message' => 'ISO reference support requires ext-intl or symfony/polyfill-intl-icu. Enable the extension or install the polyfill with composer require symfony/polyfill-intl-icu.',
        ])
        ->and($result['catalog'])->toBe($result['country']);
});
