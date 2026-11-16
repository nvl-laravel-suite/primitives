<?php

declare(strict_types=1);

use Nvl\Primitives\Contracts\ReferenceCodeDirectory;
use Nvl\Primitives\Contracts\ReferenceDirectory;
use Nvl\Primitives\Tests\Fixtures\InMemoryReferenceDirectory;

function referenceDirectory(): InMemoryReferenceDirectory
{
    return new InMemoryReferenceDirectory([
        'warehouses' => [
            ['id' => '7', 'code' => 'SOF', 'label' => 'Sofia Central', 'metadata' => ['active' => true, 'capacity' => 120]],
            ['id' => '12', 'code' => '7', 'label' => 'Plovdiv', 'metadata' => ['region' => null]],
            ['id' => '3', 'code' => null, 'label' => 'Burgas 100% Port', 'metadata' => []],
            ['id' => '9', 'code' => 'VAR', 'label' => 'Burgas North', 'metadata' => ['ratio' => 0.5]],
        ],
        'carriers' => [
            ['id' => '7', 'code' => 'DHL', 'label' => 'DHL Express', 'metadata' => []],
        ],
    ]);
}

it('implements the original directory contract', function (): void {
    expect(referenceDirectory())->toBeInstanceOf(ReferenceDirectory::class)
        ->and(referenceDirectory())->toBeInstanceOf(ReferenceCodeDirectory::class);
});

it('finds records by exact id and by exact code without collisions', function (): void {
    $directory = referenceDirectory();

    expect($directory->find('warehouses', '7')['label'] ?? null)->toBe('Sofia Central')
        ->and($directory->findByCode('warehouses', '7')['label'] ?? null)->toBe('Plovdiv')
        ->and($directory->findByCode('warehouses', 'SOF')['id'] ?? null)->toBe('7')
        ->and($directory->find('warehouses', 'SOF'))->toBeNull()
        ->and($directory->findByCode('warehouses', 'sof'))->toBeNull()
        ->and($directory->find('warehouses', '07'))->toBeNull();
});

it('isolates catalogs and returns null or nothing for missing entries', function (): void {
    $directory = referenceDirectory();

    expect($directory->find('carriers', '7')['label'] ?? null)->toBe('DHL Express')
        ->and($directory->findByCode('carriers', 'SOF'))->toBeNull()
        ->and($directory->find('warehouses', '404'))->toBeNull()
        ->and($directory->findByCode('warehouses', ''))->toBeNull()
        ->and($directory->find('unknown', '7'))->toBeNull()
        ->and($directory->findByCode('unknown', 'SOF'))->toBeNull()
        ->and($directory->search('unknown'))->toBe([]);
});

it('searches labels and codes literally after trimming and case folding', function (): void {
    $directory = referenceDirectory();
    $ids = static fn (array $records): array => array_column($records, 'id');

    expect($ids($directory->search('warehouses', '  sofia ')))->toBe(['7'])
        ->and($ids($directory->search('warehouses', 'var')))->toBe(['9'])
        ->and($ids($directory->search('warehouses', '%')))->toBe(['3'])
        ->and($ids($directory->search('warehouses', '_')))->toBe([])
        ->and($ids($directory->search('carriers', 'sofia')))->toBe([]);
});

it('lists every record ordered by label then id within the limit', function (): void {
    $directory = referenceDirectory();
    $results = $directory->search('warehouses', '   ');

    expect(array_column($results, 'id'))->toBe(['3', '9', '12', '7'])
        ->and(array_keys($results))->toBe([0, 1, 2, 3])
        ->and(array_column($directory->search('warehouses', null, 2), 'id'))->toBe(['3', '9'])
        ->and(array_column($directory->search('warehouses', 'burgas', 1), 'id'))->toBe(['3'])
        ->and(fn () => $directory->search('warehouses', null, 0))->toThrow(InvalidArgumentException::class)
        ->and(fn () => $directory->search('warehouses', null, 101))->toThrow(InvalidArgumentException::class)
        ->and($directory->search('warehouses', null, 100))->toHaveCount(4);

    $tied = new InMemoryReferenceDirectory(['depots' => [
        ['id' => '7', 'code' => null, 'label' => 'Same', 'metadata' => []],
        ['id' => '12', 'code' => null, 'label' => 'Same', 'metadata' => []],
    ]]);

    expect(array_column($tied->search('depots'), 'id'))->toBe(['12', '7']);
});

it('returns the complete record shape with its scalar metadata', function (): void {
    expect(referenceDirectory()->find('warehouses', '7'))->toBe([
        'id' => '7', 'code' => 'SOF', 'label' => 'Sofia Central', 'metadata' => ['active' => true, 'capacity' => 120],
    ])->and(referenceDirectory()->findByCode('warehouses', 'VAR'))->toBe([
        'id' => '9', 'code' => 'VAR', 'label' => 'Burgas North', 'metadata' => ['ratio' => 0.5],
    ])->and(referenceDirectory()->find('warehouses', '3'))->toBe([
        'id' => '3', 'code' => null, 'label' => 'Burgas 100% Port', 'metadata' => [],
    ]);
});
