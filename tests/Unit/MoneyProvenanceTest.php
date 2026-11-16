<?php

declare(strict_types=1);

use Nvl\Primitives\Exceptions\InvalidPrimitive;
use Nvl\Primitives\Support\BrickMathCompatibility;
use Nvl\Primitives\ValueObjects\Money;

function convertedMoney(string $amount, string $currency, string $sourceAmount, string $sourceCurrency): Money
{
    return Money::of($amount, $currency)->withSource(Money::of($sourceAmount, $sourceCurrency));
}

it('adds and subtracts compatible source amounts', function (): void {
    $first = convertedMoney('1.00', 'EUR', '1.96', 'BGN');
    $second = convertedMoney('2.00', 'EUR', '3.91', 'BGN');

    $sum = $first->add($second);
    $difference = $first->subtract($second);

    expect($sum->toArray())->toBe(['minor' => '300', 'currency' => 'EUR'])
        ->and($sum->source()->toArray())->toBe(['minor' => '587', 'currency' => 'BGN'])
        ->and($difference->toArray())->toBe(['minor' => '-100', 'currency' => 'EUR'])
        ->and($difference->source()->toArray())->toBe(['minor' => '-195', 'currency' => 'BGN']);
});

it('drops provenance when source currencies differ', function (): void {
    $fromLev = convertedMoney('1.00', 'EUR', '1.96', 'BGN');
    $fromPound = convertedMoney('2.00', 'EUR', '1.70', 'GBP');
    $plain = Money::of('3.00', 'EUR');

    foreach ([$fromLev->add($fromPound), $fromLev->subtract($fromPound), $fromLev->add($plain), $plain->add($fromLev)] as $result) {
        expect($result->source())->toBe($result);
    }
});

it('rounds the primary and source amounts independently', function (): void {
    $value = Money::minor(2, 'EUR')->withSource(Money::minor(3, 'BGN'));
    $halved = $value->divide(2, BrickMathCompatibility::halfUp());
    $doubled = $value->multiply('0.5', BrickMathCompatibility::halfUp());

    expect(fn () => $value->divide(2))->toThrow(InvalidPrimitive::class)
        ->and(fn () => $value->multiply('0.5'))->toThrow(InvalidPrimitive::class)
        ->and($halved->minorAmount())->toBe('1')
        ->and($halved->source()->toArray())->toBe(['minor' => '2', 'currency' => 'BGN'])
        ->and($doubled->minorAmount())->toBe('1')
        ->and($doubled->source()->minorAmount())->toBe('2');
});

it('keeps the source side through sign changes of a converted zero', function (): void {
    $zero = Money::zero('EUR')->withSource(Money::of('0.01', 'BGN'));
    $negated = $zero->negate();

    expect($negated->isZero())->toBeTrue()
        ->and($negated->source()->toArray())->toBe(['minor' => '-1', 'currency' => 'BGN'])
        ->and($negated->absolute()->source()->toArray())->toBe(['minor' => '1', 'currency' => 'BGN'])
        ->and($zero->divide(2, BrickMathCompatibility::halfUp())->source()->minorAmount())->toBe('1');
});

it('conserves both totals when allocating and splitting', function (): void {
    $value = Money::minor(5, 'EUR')->withSource(Money::minor(9, 'BGN'));

    $describe = static fn (array $pieces): array => array_map(
        static fn (Money $piece): array => [$piece->minorAmount(), $piece->source()->minorAmount()],
        $pieces,
    );

    expect($describe($value->split(2)))->toBe([['3', '5'], ['2', '4']])
        ->and($describe($value->allocate([1, 1])))->toBe([['3', '5'], ['2', '4']])
        ->and($value->split(2)[0]->source()->currency()->storageValue())->toBe('BGN');
});

it('leaves operands unchanged and never nests provenance', function (): void {
    $first = convertedMoney('1.00', 'EUR', '1.96', 'BGN');
    $second = convertedMoney('2.00', 'EUR', '3.91', 'BGN');
    $rounding = BrickMathCompatibility::halfUp();

    $results = [
        $first->add($second),
        $first->subtract($second),
        $first->multiply(3),
        $first->divide(3, $rounding),
        $first->absolute(),
        $first->negate(),
        ...$first->split(2),
        ...$first->allocate([1, 2]),
    ];

    foreach ($results as $result) {
        expect($result->source()->source())->toBe($result->source())
            ->and($result->source()->currency()->storageValue())->toBe('BGN');
    }

    expect($first->toArray())->toBe(['minor' => '100', 'currency' => 'EUR'])
        ->and($first->source()->toArray())->toBe(['minor' => '196', 'currency' => 'BGN'])
        ->and($second->source()->toArray())->toBe(['minor' => '391', 'currency' => 'BGN']);
});

it('rejects an inexact primary result with an exact source under Unnecessary', function (): void {
    $value = Money::minor(3, 'EUR')->withSource(Money::minor(2, 'BGN'));
    $unnecessary = BrickMathCompatibility::unnecessary();
    $halved = $value->divide(2, BrickMathCompatibility::halfUp());

    expect(fn () => $value->divide(2, $unnecessary))->toThrow(InvalidPrimitive::class)
        ->and(fn () => $value->multiply('0.5', $unnecessary))->toThrow(InvalidPrimitive::class)
        ->and(fn () => $value->divide(2))->toThrow(InvalidPrimitive::class)
        ->and($value->source()->divide(2, $unnecessary)->minorAmount())->toBe('1')
        ->and($halved->minorAmount())->toBe('2')
        ->and($halved->source()->minorAmount())->toBe('1');
});
