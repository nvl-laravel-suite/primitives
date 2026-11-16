<?php

declare(strict_types=1);

namespace Nvl\Primitives\Contracts;

/**
 * Host-provided lookup for record-backed reference values.
 *
 * @api
 *
 * @phpstan-type ReferenceRecord array{id:string,code:?string,label:string,metadata:array<string,bool|int|float|string|null>}
 */
interface ReferenceDirectory
{
    /** @return ReferenceRecord|null */
    public function find(string $catalog, string $id): ?array;

    /** @return list<ReferenceRecord> */
    public function search(string $catalog, ?string $query = null, int $limit = 50): array;
}
