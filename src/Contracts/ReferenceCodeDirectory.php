<?php

declare(strict_types=1);

namespace Nvl\Primitives\Contracts;

/**
 * Optional reference directory extension that also resolves records by their stable code.
 *
 * @api
 *
 * @phpstan-import-type ReferenceRecord from ReferenceDirectory
 */
interface ReferenceCodeDirectory extends ReferenceDirectory
{
    /** @return ReferenceRecord|null */
    public function findByCode(string $catalog, string $code): ?array;
}
