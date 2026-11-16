<?php

declare(strict_types=1);

namespace Nvl\Primitives\ValueObjects;

/**
 * Selects localization, optional associated amounts and numeric output for a money presentation.
 *
 * @api
 */
final readonly class PresentationOptions
{
    public function __construct(
        public ?string $locale = null,
        public ?Money $record = null,
        public ?Money $pair = null,
        public bool $numeric = false,
    ) {}
}
