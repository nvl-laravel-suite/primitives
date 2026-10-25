<?php

declare(strict_types=1);

namespace Nvl\Primitives\Support;

use Iban\Validation\Validator;
use libphonenumber\PhoneNumberUtil;
use RuntimeException;
use Symfony\Component\Intl\Countries;

/** Checks optional domain libraries at the point their capabilities are used. */
final class OptionalLibraries
{
    /** Require the numbering plan registry for phone parsing and formatting. */
    public static function phoneNumbers(): void
    {
        if (! class_exists(PhoneNumberUtil::class)) {
            throw new RuntimeException('Phone number support requires giggsey/libphonenumber-for-php. Install it with composer require giggsey/libphonenumber-for-php.');
        }
    }

    /** Require the IBAN registry for validation and formatting. */
    public static function iban(): void
    {
        if (! class_exists(Validator::class)) {
            throw new RuntimeException('IBAN support requires jschaedl/iban-validation. Install it with composer require jschaedl/iban-validation.');
        }
    }

    /** Require ISO name and metadata tables for reference operations. */
    public static function intl(): void
    {
        if (! class_exists(Countries::class)) {
            throw new RuntimeException('ISO reference support requires symfony/intl. Install it with composer require symfony/intl.');
        }

        if (! class_exists(\Locale::class)) {
            throw new RuntimeException('ISO reference support requires ext-intl or symfony/polyfill-intl-icu. Enable the extension or install the polyfill with composer require symfony/polyfill-intl-icu.');
        }
    }
}
