<?php

declare(strict_types=1);

namespace Nvl\Primitives\ValueObjects;

/**
 * Selects localization and optional associated amounts for a money presentation.
 *
 * @api
 */
final readonly class PresentationOptions
{
    public function __construct(public ?string $locale = null, public ?Money $record = null, public ?Money $pair = null) {}
}
