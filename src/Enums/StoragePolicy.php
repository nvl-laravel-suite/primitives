<?php

declare(strict_types=1);

namespace Nvl\Primitives\Enums;

/**
 * Selects how an amount in another currency can be prepared for a record.
 *
 * @api
 */
enum StoragePolicy: string
{
    case Preserve = 'preserve';
    case Adopt = 'adopt';
    case Require = 'require';
    case MigrateParent = 'migrate_parent';
}
