<?php

declare(strict_types=1);

use Brick\Math\RoundingMode;
use Nvl\Primitives\Exceptions\InvalidPrimitive;
use Nvl\Primitives\Rules\ValidPrimitive;
use Nvl\Primitives\Services\ReferenceCatalog;
use Nvl\Primitives\Support\BrickMathCompatibility;
use Nvl\Primitives\ValueObjects\Coordinates;
use Nvl\Primitives\ValueObjects\Length;
use Nvl\Primitives\ValueObjects\LocaleCode;
use Nvl\Primitives\ValueObjects\Money;
use Nvl\Primitives\ValueObjects\Percentage;
use Nvl\Primitives\ValueObjects\Weight;

it('rejects locale subtags that end in a line break', function (string $value): void {
    expect(LocaleCode::tryFrom($value))->toBeNull();
})->with([
    'numeric region' => "es-419\n-valencia",
    'variant' => "ca-valencia\n-fonipa",
    'alpha region' => "en-US\n-valencia",
    'script' => "zh-Hans\n-CN",
]);

it('refuses decimal exponents that would expand into an enormous number', function (string $value): void {
    expect(fn () => Weight::from($value))->toThrow(InvalidPrimitive::class)
        ->and(fn () => Weight::kilograms($value))->toThrow(InvalidPrimitive::class)
        ->and(fn () => Percentage::from($value))->toThrow(InvalidPrimitive::class)
        ->and(fn () => Percentage::fromPercent($value))->toThrow(InvalidPrimitive::class)
        ->and(fn () => Percentage::full()->of($value))->toThrow(InvalidPrimitive::class)
        ->and(fn () => Percentage::full()->ofRounded($value, 2, RoundingMode::HalfUp))
        ->toThrow(InvalidPrimitive::class)
        ->and(fn () => Length::from($value))->toThrow(InvalidPrimitive::class)
        ->and(fn () => Length::fromArray(['value' => $value, 'unit' => 'm']))->toThrow(InvalidPrimitive::class)
        ->and(fn () => Coordinates::from($value, '0'))->toThrow(InvalidPrimitive::class)
        ->and(fn () => Coordinates::from('0', $value))->toThrow(InvalidPrimitive::class)
        ->and(fn () => Money::of($value, 'EUR'))->toThrow(InvalidPrimitive::class)
        ->and(fn () => Money::minor($value, 'EUR'))->toThrow(InvalidPrimitive::class)
        ->and(fn () => Money::fromArray(['amount' => $value, 'currency' => 'EUR']))->toThrow(InvalidPrimitive::class)
        ->and(fn () => Money::of('1', 'EUR')->multiply($value, RoundingMode::HalfUp))
        ->toThrow(InvalidPrimitive::class)
        ->and(fn () => Money::of('1', 'EUR')->divide($value, RoundingMode::HalfUp))
        ->toThrow(InvalidPrimitive::class)
        ->and(fn () => Money::of('1', 'EUR')->convert('USD', $value, RoundingMode::HalfUp))
        ->toThrow(InvalidPrimitive::class);
})->with([
    'tiny' => '1e-999999999',
    'huge' => '1e999999999',
    'explicit sign' => '1E+999999999',
]);

it('reports an out-of-range exponent as a validation failure instead of exhausting memory', function (): void {
    $validator = validator(
        ['weight' => '1e-999999999'],
        ['weight' => [new ValidPrimitive(Weight::class, 'weight')]],
    );

    expect($validator->fails())->toBeTrue();
});

it('keeps ordinary scientific notation exact', function (): void {
    expect(Weight::from('1.5e3')->storageValue())->toBe('1500')
        ->and(Percentage::from('5e-2')->percent())->toBe('5')
        ->and(Length::from('1e-3', 'km')->toArray())->toBe(['value' => '1', 'unit' => 'm'])
        ->and(Money::of('1.5e1', 'EUR')->amount())->toBe('15.00');
});

it('normalizes an inexact rounded percent to the primitive exception', function (): void {
    expect(fn () => Percentage::from('0.12345')->percentRounded(1, BrickMathCompatibility::unnecessary()))
        ->toThrow(InvalidPrimitive::class)
        ->and(Percentage::from('0.12345')->percentRounded(1, RoundingMode::HalfUp))->toBe('12.3');
});

it('lists configured reference entries whose codes are numeric', function (): void {
    config()->set('nvl-primitives.reference.banks', [
        '10010010' => ['label' => 'Postbank', 'country' => 'DE'],
        'UNCRBGSF' => ['label' => 'UniCredit Bulbank', 'country' => 'BG'],
    ]);
    config()->set('nvl-primitives.reference.cities', [
        '1000' => ['label' => 'Sofia'],
    ]);
    $catalog = app(ReferenceCatalog::class);

    expect(array_column($catalog->banks(), 'code'))->toBe(['10010010', 'UNCRBGSF'])
        ->and($catalog->banks('1001'))->toHaveCount(1)
        ->and($catalog->cities()[0])->toBe(['code' => '1000', 'label' => 'Sofia', 'metadata' => []]);

    config()->set('nvl-primitives.reference.cities', [['label' => 'Sofia'], ['label' => 'Plovdiv']]);

    expect(fn () => $catalog->cities())->toThrow(InvalidPrimitive::class);
});
