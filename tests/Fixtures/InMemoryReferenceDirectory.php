<?php

declare(strict_types=1);

namespace Nvl\Primitives\Tests\Fixtures;

use InvalidArgumentException;
use Nvl\Primitives\Contracts\ReferenceCodeDirectory;
use Nvl\Primitives\Contracts\ReferenceDirectory;

/**
 * Array-backed reference directory that pins the documented lookup and search contract.
 *
 * @phpstan-import-type ReferenceRecord from ReferenceDirectory
 */
final readonly class InMemoryReferenceDirectory implements ReferenceCodeDirectory
{
    /**
     * @param  array<string, list<ReferenceRecord>>  $catalogs
     */
    public function __construct(private array $catalogs) {}

    /** @return ReferenceRecord|null */
    public function find(string $catalog, string $id): ?array
    {
        foreach ($this->catalogs[$catalog] ?? [] as $record) {
            if ($record['id'] === $id) {
                return $record;
            }
        }

        return null;
    }

    /** @return ReferenceRecord|null */
    public function findByCode(string $catalog, string $code): ?array
    {
        foreach ($this->catalogs[$catalog] ?? [] as $record) {
            if ($record['code'] === $code) {
                return $record;
            }
        }

        return null;
    }

    /** @return list<ReferenceRecord> */
    public function search(string $catalog, ?string $query = null, int $limit = 50): array
    {
        if ($limit < 1 || $limit > 100) {
            throw new InvalidArgumentException('The reference search limit must be between 1 and 100.');
        }

        $needle = mb_strtolower(trim($query ?? ''));
        $matches = array_filter(
            $this->catalogs[$catalog] ?? [],
            static fn (array $record): bool => $needle === ''
                || str_contains(mb_strtolower($record['label']), $needle)
                || str_contains(mb_strtolower($record['code'] ?? ''), $needle),
        );

        usort(
            $matches,
            static fn (array $left, array $right): int => strcmp($left['label'], $right['label']) ?: strcmp($left['id'], $right['id']),
        );

        return array_slice($matches, 0, $limit);
    }
}
