<?php

declare(strict_types=1);

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Nvl\Primitives\Casts\ArrayPrimitiveCast;
use Nvl\Primitives\Casts\DateTimeCast;
use Nvl\Primitives\Casts\MoneyCast;
use Nvl\Primitives\Casts\ScalarPrimitiveCast;
use Nvl\Primitives\Contracts\HasRecordCurrency;
use Nvl\Primitives\Exceptions\InvalidPrimitive;
use Nvl\Primitives\Tests\Fixtures\PrimitiveTestModel;
use Nvl\Primitives\ValueObjects\Coordinates;
use Nvl\Primitives\ValueObjects\DateTimeValue;
use Nvl\Primitives\ValueObjects\EmailAddress;
use Nvl\Primitives\ValueObjects\Identifier;
use Nvl\Primitives\ValueObjects\Money;
use Nvl\Primitives\ValueObjects\Percentage;

it('rejects boolean and floating point input before scalar primitive coercion', function (mixed $value): void {
    $model = new PrimitiveTestModel;
    foreach ([Identifier::class, Percentage::class] as $class) {
        $cast = new ScalarPrimitiveCast($class);
        expect(fn () => $cast->set($model, 'value', $value, []))->toThrow(InvalidArgumentException::class);
    }
})->with([true, false, 1.25, 1.0000000000000002]);

it('rejects noncanonical scalar storage before hydration', function (mixed $value): void {
    expect(fn () => (new ScalarPrimitiveCast(Identifier::class))->get(new PrimitiveTestModel, 'value', $value, []))
        ->toThrow(InvalidArgumentException::class);
})->with([true, false, INF, -INF, NAN]);

it('preserves exact scalar input while retaining native database float hydration', function (): void {
    $cast = new ScalarPrimitiveCast(Percentage::class);
    $model = new PrimitiveTestModel;
    expect($cast->set($model, 'ratio', 1, []))->toBe('1')
        ->and(Percentage::castUsing([])->set($model, 'ratio', 1, []))->toBe('1')
        ->and($cast->set($model, 'ratio', '1.0000000000000002', []))->toBe('1.0000000000000002')
        ->and($cast->get($model, 'ratio', 1.25, [])?->storageValue())->toBe('1.25');
});

it('treats only actual null as an absent scalar primitive', function (): void {
    $cast = new ScalarPrimitiveCast(EmailAddress::class);
    $model = new PrimitiveTestModel;

    expect($cast->get($model, 'email', null, []))->toBeNull()
        ->and($cast->set($model, 'email', null, []))->toBeNull()
        ->and(fn () => $cast->get($model, 'email', '', []))->toThrow(InvalidPrimitive::class)
        ->and(fn () => $cast->set($model, 'email', '', []))->toThrow(InvalidPrimitive::class);
});

it('treats empty JSON strings as malformed array primitives', function (): void {
    $cast = new ArrayPrimitiveCast(Coordinates::class);
    $model = new PrimitiveTestModel;

    expect($cast->get($model, 'coordinates', null, []))->toBeNull()
        ->and(fn () => $cast->get($model, 'coordinates', '', []))->toThrow(JsonException::class)
        ->and(fn () => $cast->set($model, 'coordinates', '', []))->toThrow(JsonException::class);
});

it('accepts scalar values in fixed-currency money modes', function (): void {
    $model = new PrimitiveTestModel;
    $minor = new MoneyCast('minor', 'EUR');
    $decimal = new MoneyCast('decimal', 'EUR');

    expect($minor->set($model, 'money_minor', 1234, []))->toBe('1234')
        ->and($minor->set($model, 'money_minor', '1234', []))->toBe('1234')
        ->and($decimal->set($model, 'money_decimal', '12.34', []))->toBe('12.34')
        ->and($minor->get($model, 'money_minor', '1234', [])?->amount())->toBe('12.34');
});

it('normalizes database-hydrated decimal floats without accepting input floats', function (): void {
    $model = new PrimitiveTestModel;
    $decimal = new MoneyCast('decimal', 'EUR');

    expect($decimal->get($model, 'money_decimal', 12.34, [])?->amount())->toBe('12.34')
        ->and(fn () => $decimal->get($model, 'money_decimal', INF, []))
        ->toThrow(InvalidArgumentException::class)
        ->and(fn () => $decimal->set($model, 'money_decimal', 12.34, []))
        ->toThrow(InvalidArgumentException::class);
});

it('validates fixed-currency cast configuration immediately', function (): void {
    expect(fn () => new MoneyCast('minor', 'invalid'))
        ->toThrow(InvalidPrimitive::class);
});

it('writes canonical money JSON and rejects empty storage', function (): void {
    $cast = new MoneyCast;
    $model = new PrimitiveTestModel;

    expect($cast->set($model, 'money', Money::of('12.34', 'EUR'), []))
        ->toBe('{"minor":"1234","currency":"EUR"}')
        ->and($cast->get($model, 'money', null, []))->toBeNull()
        ->and(fn () => $cast->get($model, 'money', '', []))->toThrow(JsonException::class);
});

it('covers scalar cast hydration and storage boundaries', function (): void {
    $cast = new ScalarPrimitiveCast(EmailAddress::class);
    $model = new PrimitiveTestModel;
    $email = EmailAddress::from('person@example.com');

    expect($cast->get($model, 'email', 'person@example.com', []))->toEqual($email)
        ->and($cast->set($model, 'email', $email, []))->toBe('person@example.com')
        ->and($cast->set($model, 'email', 'Person@EXAMPLE.COM', []))
        ->toBe('Person@example.com')
        ->and(fn () => $cast->get($model, 'email', [], []))
        ->toThrow(InvalidArgumentException::class)
        ->and(fn () => $cast->set($model, 'email', [], []))
        ->toThrow(InvalidArgumentException::class);
});

it('covers array cast hydration and storage boundaries', function (): void {
    $cast = new ArrayPrimitiveCast(Coordinates::class);
    $model = new PrimitiveTestModel;
    $coordinates = Coordinates::from('42.6977', '23.3219');
    $payload = ['latitude' => '42.6977', 'longitude' => '23.3219'];

    expect($cast->get($model, 'coordinates', json_encode($payload, JSON_THROW_ON_ERROR), []))
        ->toEqual($coordinates)
        ->and($cast->set($model, 'coordinates', null, []))->toBeNull()
        ->and($cast->set($model, 'coordinates', $coordinates, []))
        ->toBe('{"latitude":"42.6977000","longitude":"23.3219000"}')
        ->and($cast->set($model, 'coordinates', $payload, []))
        ->toBe('{"latitude":"42.6977000","longitude":"23.3219000"}')
        ->and($cast->set($model, 'coordinates', json_encode($payload, JSON_THROW_ON_ERROR), []))
        ->toBe('{"latitude":"42.6977000","longitude":"23.3219000"}')
        ->and(fn () => $cast->get($model, 'coordinates', '"invalid"', []))
        ->toThrow(InvalidArgumentException::class)
        ->and(fn () => $cast->set($model, 'coordinates', 42, []))
        ->toThrow(InvalidArgumentException::class);
});

it('covers every money cast mode and rejection boundary', function (): void {
    $model = new PrimitiveTestModel;
    $json = new MoneyCast;
    $minor = new MoneyCast('minor', 'EUR');
    $decimal = new MoneyCast('decimal', 'EUR');

    expect($minor->get($model, 'money_minor', null, []))->toBeNull()
        ->and($decimal->get($model, 'money_decimal', 12, [])?->amount())->toBe('12.00')
        ->and($json->get($model, 'money', ['minor' => '1234', 'currency' => 'EUR'], [])?->amount())
        ->toBe('12.34')
        ->and($json->set($model, 'money', ['amount' => '12.34', 'currency' => 'EUR'], []))
        ->toBe('{"minor":"1234","currency":"EUR"}')
        ->and($json->set(
            $model,
            'money',
            '{"amount":"12.34","currency":"EUR"}',
            [],
        ))->toBe('{"minor":"1234","currency":"EUR"}')
        ->and($decimal->set($model, 'money_decimal', Money::of('12.34', 'EUR'), []))
        ->toBe('12.34')
        ->and(fn () => new MoneyCast('unsupported'))
        ->toThrow(InvalidArgumentException::class)
        ->and(fn () => new MoneyCast('minor'))
        ->toThrow(InvalidArgumentException::class)
        ->and(fn () => $minor->get($model, 'money_minor', 1.5, []))
        ->toThrow(InvalidArgumentException::class)
        ->and(fn () => $decimal->get($model, 'money_decimal', [], []))
        ->toThrow(InvalidArgumentException::class)
        ->and(fn () => $json->get($model, 'money', '"invalid"', []))
        ->toThrow(InvalidArgumentException::class)
        ->and(fn () => $json->set($model, 'money', 42, []))
        ->toThrow(InvalidArgumentException::class)
        ->and(fn () => $json->set($model, 'money', [0 => 'invalid'], []))
        ->toThrow(InvalidArgumentException::class)
        ->and(fn () => $minor->set($model, 'money_minor', Money::of('1.00', 'USD'), []))
        ->toThrow(InvalidArgumentException::class);
});

it('enforces the configured currency when hydrating json money', function (): void {
    $model = new PrimitiveTestModel;
    $cast = new MoneyCast('json', 'EUR');

    expect($cast->get($model, 'money', '{"minor":"1234","currency":"EUR"}', [])?->amount())
        ->toBe('12.34')
        ->and(fn () => $cast->get($model, 'money', '{"minor":"1234","currency":"USD"}', []))
        ->toThrow(InvalidArgumentException::class);
});

it('persists native scalar and array inputs without losing exact money units', function (): void {
    $model = PrimitiveTestModel::query()->create([
        'email' => 'Person@EXAMPLE.com',
        'coordinates' => ['latitude' => '42.6977001', 'longitude' => '23.3219001'],
        'money' => ['minor' => '9007199254740993', 'currency' => 'EUR'],
    ])->fresh();

    $coordinates = json_decode($model?->getRawOriginal('coordinates'), associative: true, flags: JSON_THROW_ON_ERROR);
    $money = json_decode($model?->getRawOriginal('money'), associative: true, flags: JSON_THROW_ON_ERROR);
    ksort($coordinates);
    ksort($money);

    expect($model?->getRawOriginal('email'))->toBe('Person@example.com')
        ->and($model?->email?->storageValue())->toBe('Person@example.com')
        ->and($coordinates)->toBe(['latitude' => '42.6977001', 'longitude' => '23.3219001'])
        ->and($model?->coordinates?->toArray())->toBe([
            'latitude' => '42.6977001',
            'longitude' => '23.3219001',
        ])
        ->and($money)->toBe(['currency' => 'EUR', 'minor' => '9007199254740993'])
        ->and($model?->money?->minorAmount())->toBe('9007199254740993');
});
it('reads record currency from a parent and writes only the configured money columns', function (): void {
    $model = new class extends Model implements HasRecordCurrency
    {
        public function getRecordCurrency(): string
        {
            return $this->getRelation('parent')->currency;
        }

        protected function casts(): array
        {
            return [
                'price' => Money::class.':decimal,record,price_minor',
                'adjustment' => Money::class.':minor,record,adjustment_minor',
            ];
        }
    };
    $parent = (new class extends Model {})->forceFill(['currency' => 'BGN']);
    $model->setRelation('parent', $parent);
    $model->setRawAttributes(['price' => '8.70', 'price_minor' => '195', 'adjustment_minor' => '0'], true);
    expect($model->price->amount())->toBe('1.95')
        ->and((string) $model->price->currency())->toBe('BGN')
        ->and($model->adjustment->minorAmount())->toBe('0')
        ->and($model->getAttributes()['price'])->toBe('8.70');

    $model->price = Money::minor(521, 'BGN');
    $model->adjustment = Money::minor(-6, 'BGN');
    expect($model->getAttributes())->toBe(['price' => '5.21', 'price_minor' => '521', 'adjustment_minor' => '-6']);
    $model->price = null;
    $model->adjustment = null;
    expect($model->getAttributes())->toBe(['price' => null, 'price_minor' => null, 'adjustment_minor' => null])
        ->and($model->price)->toBeNull()->and($model->adjustment)->toBeNull();
});

it('round-trips distinct instants in the repeated local hour through both SQL timestamp modes', function (): void {
    $model = new class extends Model {};
    $first = DateTimeValue::from('2026-10-25T03:30:00.253421+03:00');
    $second = DateTimeValue::from('2026-10-25T03:30:00.253421+02:00');
    foreach ([new DateTimeCast('timestamp'), new DateTimeCast('timestamptz')] as $cast) {
        $storedFirst = $cast->set($model, 'occurred_at', $first, []);
        $storedSecond = $cast->set($model, 'occurred_at', $second, []);
        expect($storedFirst)->not->toBe($storedSecond)
            ->and($cast->get($model, 'occurred_at', $storedFirst, [])->equals($first))->toBeTrue()
            ->and($cast->get($model, 'occurred_at', $storedSecond, [])->equals($second))->toBeTrue();
    }
    $local = new DateTimeCast('timestamp', 'Europe/Sofia');
    expect(fn () => $local->get($model, 'occurred_at', '2026-10-25 03:30:00', []))->toThrow(InvalidPrimitive::class, 'ambiguous')
        ->and(fn () => $local->get($model, 'occurred_at', '2026-03-29 03:30:00', []))->toThrow(InvalidPrimitive::class)
        ->and($local->get($model, 'occurred_at', '2026-10-25 04:30:00', [])->storageValue())->toBe('2026-10-25T02:30:00.000000Z');
});

it('persists both money columns and timestamp modes through Eloquent', function (): void {
    $early = DateTimeValue::from('2026-10-25T03:30:00.253421+03:00');
    $late = DateTimeValue::from('2026-10-25T03:30:00.253421+02:00');
    $model = PrimitiveTestModel::query()->create([
        'currency' => 'BGN', 'record_price' => Money::minor(0, 'BGN'), 'sql_timestamp' => $early, 'sql_timestamp_tz' => $late,
    ])->fresh();

    expect((string) $model->getRawOriginal('record_price_minor'))->toBe('0')
        ->and($model->record_price->amount())->toBe('0.00')
        ->and($model->sql_timestamp->equals($early))->toBeTrue()
        ->and($model->sql_timestamp_tz->equalTo($late->carbon()))->toBeTrue()
        ->and($model->isDirty())->toBeFalse();

    $model->update(['record_price' => Money::minor(246, 'BGN')]);
    expect((string) $model->getRawOriginal('record_price'))->toBe('2.46')
        ->and((string) $model->getRawOriginal('record_price_minor'))->toBe('246');
});

it('never falls back from a required minor twin', function (): void {
    $id = PrimitiveTestModel::query()->create(['strict_price' => Money::of('82.56', 'EUR')])->getKey();
    $store = static fn (?string $decimal, ?int $minor) => DB::table('primitive_test_models')->where('id', $id)
        ->update(['strict_price' => $decimal, 'strict_price_minor' => $minor]);
    $load = static fn (array $columns = ['*']) => PrimitiveTestModel::query()->select($columns)->findOrFail($id);

    $store('90.00', 8256);
    expect($load()->strict_price->amount())->toBe('82.56')
        ->and(fn () => $load(['id', 'strict_price'])->strict_price)->toThrow(InvalidArgumentException::class, 'to be loaded')
        ->and($load(['id', 'strict_price_minor'])->strict_price->amount())->toBe('82.56');

    $store('90.00', 0);
    expect($load()->strict_price->amount())->toBe('0.00');

    $store('90.00', null);
    expect(fn () => $load()->strict_price)->toThrow(InvalidArgumentException::class, 'null authoritative minor column');

    $store(null, null);
    expect($load()->strict_price)->toBeNull();

    $unsaved = new PrimitiveTestModel;
    $unsaved->strict_price = '90.00';
    expect($unsaved->getAttributes())->toMatchArray(['strict_price' => '90.00', 'strict_price_minor' => '9000'])
        ->and($unsaved->strict_price->amount())->toBe('90.00')
        ->and(fn () => (new PrimitiveTestModel)->setRawAttributes(['strict_price' => '90.00'])->strict_price)
        ->toThrow(InvalidArgumentException::class, 'to be loaded')
        ->and(fn () => new MoneyCast('decimal', 'EUR', authority: 'strict'))->toThrow(InvalidArgumentException::class, 'requires a minor column')
        ->and(fn () => new MoneyCast('minor', 'EUR', authority: 'loose'))->toThrow(InvalidArgumentException::class, 'Unsupported money cast authority');
});

it('reassigning an equivalent SQL instant stays clean', function (string $cast, string $stored): void {
    $model = (new class extends Model {})->mergeCasts(['occurred_at' => DateTimeCast::class.':'.$cast]);
    $model->setRawAttributes(['occurred_at' => $stored], true);

    $model->occurred_at = $model->occurred_at;
    expect($model->isDirty('occurred_at'))->toBeFalse();

    $model->occurred_at = DateTimeValue::from('2026-01-01T12:00:00.000001Z');
    expect($model->isDirty('occurred_at'))->toBeTrue();

    $model->occurred_at = null;
    expect($model->isDirty('occurred_at'))->toBeTrue();

    $model->setRawAttributes(['occurred_at' => null], true);
    $model->occurred_at = null;
    expect($model->isDirty('occurred_at'))->toBeFalse();

    $model->occurred_at = DateTimeValue::from('2026-01-01T12:00:00Z');
    expect($model->isDirty('occurred_at'))->toBeTrue();
})->with([
    'timestamp as value' => ['timestamp,UTC,value', '2026-01-01 12:00:00'],
    'timestamp as carbon with fraction' => ['timestamp,UTC,carbon', '2026-01-01 12:00:00.5'],
    'local timestamp as value' => ['timestamp,Europe/Sofia,value', '2026-01-01 14:00:00'],
    'timestamptz as value' => ['timestamptz,UTC,value', '2026-01-01 12:00:00+00'],
    'timestamptz as carbon with fraction' => ['timestamptz,UTC,carbon', '2026-01-01 12:00:00.5+00'],
    'timestamptz from a non-UTC session' => ['timestamptz,UTC,value', '2026-01-01 14:00:00+02'],
    'timestamptz carbon from a non-UTC session' => ['timestamptz,UTC,carbon', '2026-01-01 14:00:00.25+02'],
]);

it('round trips exact primitives through PostgreSQL storage', function (): void {
    $large = '9007199254740993';
    $instant = DateTimeValue::from('2026-10-25T03:30:00.253421+02:00');
    $created = PrimitiveTestModel::query()->create([
        'currency' => 'BGN',
        'money' => Money::minor($large, 'EUR'),
        'record_price' => Money::minor($large, 'BGN'),
        'strict_price' => Money::minor($large, 'EUR'),
        'sql_timestamp' => $instant,
        'sql_timestamp_tz' => $instant,
    ]);
    $model = PrimitiveTestModel::query()->findOrFail($created->getKey());

    expect(Str::isUuid($model->getKey()))->toBeTrue()
        ->and((string) $model->getRawOriginal('record_price_minor'))->toBe($large)
        ->and((string) $model->getRawOriginal('strict_price_minor'))->toBe($large)
        ->and($model->money->minorAmount())->toBe($large)
        ->and($model->record_price->minorAmount())->toBe($large)
        ->and($model->strict_price->minorAmount())->toBe($large)
        ->and($model->sql_timestamp->equals($instant))->toBeTrue()
        ->and($model->sql_timestamp_tz->equalTo($instant->carbon()))->toBeTrue();

    if ($model->getConnection()->getDriverName() === 'pgsql') {
        expect($model->getConnection()->selectOne('SHOW TIME ZONE')->TimeZone)->toBe('Europe/Sofia')
            ->and($model->getRawOriginal('sql_timestamp_tz'))->toBe('2026-10-25 03:30:00.253421+02');
    }

    $model->sql_timestamp = $model->sql_timestamp;
    $model->sql_timestamp_tz = $model->sql_timestamp_tz;
    expect($model->isDirty())->toBeFalse();
});
