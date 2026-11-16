<?php

declare(strict_types=1);

namespace Nvl\Primitives\Tests\Fixtures;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Nvl\Primitives\Casts\DateTimeCast;
use Nvl\Primitives\ValueObjects\Coordinates;
use Nvl\Primitives\ValueObjects\DateTimeValue;
use Nvl\Primitives\ValueObjects\EmailAddress;
use Nvl\Primitives\ValueObjects\Identifier;
use Nvl\Primitives\ValueObjects\Length;
use Nvl\Primitives\ValueObjects\Money;
use Nvl\Primitives\ValueObjects\PhoneNumber;
use Nvl\Primitives\ValueObjects\PostalAddress;
use Nvl\Primitives\ValueObjects\TimezoneId;

/**
 * Exercises Castable integration against a real Eloquent model.
 *
 * @property EmailAddress|null $email
 * @property PhoneNumber|null $phone
 * @property Money|null $money
 * @property Money|null $money_minor
 * @property Money|null $money_decimal
 * @property Coordinates|null $coordinates
 * @property PostalAddress|null $postal_address
 * @property DateTimeValue|null $date_time
 * @property DateTimeValue|null $sql_timestamp
 * @property CarbonImmutable|null $sql_timestamp_tz
 * @property Money|null $record_price
 * @property Money|null $strict_price
 * @property TimezoneId|null $timezone
 * @property Identifier|null $external_id
 * @property Length|null $length
 */
final class PrimitiveTestModel extends Model
{
    use HasUuids;

    protected $table = 'primitive_test_models';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'email', 'phone', 'money', 'money_minor', 'money_decimal',
        'coordinates', 'postal_address', 'date_time', 'timezone',
        'external_id', 'length',
        'currency', 'record_price', 'strict_price', 'sql_timestamp', 'sql_timestamp_tz',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email' => EmailAddress::class,
            'phone' => PhoneNumber::class,
            'money' => Money::class,
            'money_minor' => Money::class.':minor,EUR',
            'money_decimal' => Money::class.':decimal,EUR',
            'record_price' => Money::class.':decimal,record,record_price_minor',
            'strict_price' => Money::class.':decimal,EUR,strict_price_minor,,strict',
            'coordinates' => Coordinates::class,
            'postal_address' => PostalAddress::class,
            'date_time' => DateTimeValue::class,
            'sql_timestamp' => DateTimeCast::class,
            'sql_timestamp_tz' => DateTimeCast::class.':timestamptz,UTC,carbon',
            'timezone' => TimezoneId::class,
            'external_id' => Identifier::class,
            'length' => Length::class,
        ];
    }
}
