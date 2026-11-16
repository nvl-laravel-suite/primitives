<?php

declare(strict_types=1);

namespace Nvl\Primitives\Casts;

use Carbon\CarbonImmutable;
use DateTimeImmutable;
use DateTimeInterface;
use DateTimeZone;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Contracts\Database\Eloquent\ComparesCastableAttributes;
use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;
use Nvl\Primitives\Exceptions\InvalidPrimitive;
use Nvl\Primitives\ValueObjects\DateTimeValue;
use Nvl\Primitives\ValueObjects\TimezoneId;

/**
 * Maps SQL timestamp columns to timezone-aware immutable values.
 *
 * @implements CastsAttributes<DateTimeValue|CarbonImmutable|null, DateTimeValue|DateTimeInterface|string|null>
 *
 * @api
 */
final readonly class DateTimeCast implements CastsAttributes, ComparesCastableAttributes
{
    private DateTimeZone $timezone;

    public bool $withoutObjectCaching;

    public function __construct(
        private string $mode = 'timestamp',
        string $timezone = 'UTC',
        private string $hydrate = 'value',
    ) {
        if (! in_array($mode, ['timestamp', 'timestamptz'], true) || ! in_array($hydrate, ['value', 'carbon'], true)) {
            throw new InvalidArgumentException('DateTimeCast requires a supported mode and hydration type.');
        }
        $this->timezone = new DateTimeZone((string) TimezoneId::from($timezone));
        $this->withoutObjectCaching = true;
    }

    /** @param array<string,mixed> $attributes */
    public function get(Model $model, string $key, mixed $value, array $attributes): DateTimeValue|CarbonImmutable|null
    {
        if ($value === null) {
            return null;
        }
        $instant = $this->instant($value);

        return $this->hydrate === 'carbon' ? $instant->carbon() : $instant;
    }

    /** @param array<string,mixed> $attributes */
    public function set(Model $model, string $key, mixed $value, array $attributes): ?string
    {
        if ($value === null) {
            return null;
        }
        $instant = $this->instant($value, assigning: true);
        $date = $this->mode === 'timestamptz' ? $instant->carbon() : $instant->carbon()->setTimezone($this->timezone);
        if (! preg_match('/^[0-9]{4}$/D', $date->format('Y')) || $date->format('Y') === '0000') {
            throw InvalidPrimitive::for('SQL date-time', 'a year between 0001 and 9999 is required.');
        }
        if ($this->mode === 'timestamptz') {
            return $date->format('Y-m-d H:i:s.uP');
        }
        $wall = $date->format('Y-m-d H:i:s.u');
        $this->wallTime($wall);

        return $wall;
    }

    /**
     * Treat stored values as equal when they denote the same instant, whatever their SQL formatting.
     */
    public function compare(Model $model, string $key, mixed $firstValue, mixed $secondValue): bool
    {
        if ($firstValue === null || $secondValue === null) {
            return $firstValue === $secondValue;
        }

        try {
            return $this->instant($firstValue, assigning: true)->equals($this->instant($secondValue, assigning: true));
        } catch (InvalidPrimitive) {
            return false;
        }
    }

    private function instant(mixed $value, bool $assigning = false): DateTimeValue
    {
        if ($value instanceof DateTimeValue) {
            return $value;
        }
        if ($value instanceof DateTimeInterface) {
            return DateTimeValue::fromDateTime($value);
        }
        if (! is_string($value) || preg_match('/^(?<day>\d{4}-\d{2}-\d{2})[ Tt](?<time>\d{2}:\d{2}:\d{2})(?:\.(?<fraction>\d{1,6}))?(?<offset>[Zz]|[+-]\d{2}(?::?\d{2})?)?$/D', $value, $fields, PREG_UNMATCHED_AS_NULL) !== 1
            || str_starts_with($fields['day'], '0000-')) {
            throw InvalidPrimitive::for('SQL date-time', 'a timestamp with valid calendar fields is required.');
        }
        $wall = $fields['day'].' '.$fields['time'].'.'.str_pad($fields['fraction'] ?? '', 6, '0');
        $offset = $fields['offset'];
        if ($offset === null) {
            if ($this->mode === 'timestamptz') {
                throw InvalidPrimitive::for('SQL date-time', 'a timestamptz requires a UTC offset.');
            }

            return $this->wallTime($wall);
        }
        if ($this->mode === 'timestamp' && ! $assigning) {
            throw InvalidPrimitive::for('SQL date-time', 'a timestamp column requires a wall time without an offset.');
        }
        if (strlen($offset) === 3) {
            $offset .= ':00';
        } elseif (strlen($offset) === 5) {
            $offset = substr($offset, 0, 3).':'.substr($offset, 3);
        }

        return DateTimeValue::from(str_replace(' ', 'T', $wall).$offset);
    }

    private function wallTime(string $wall): DateTimeValue
    {
        $parsed = DateTimeImmutable::createFromFormat('!Y-m-d H:i:s.u', $wall, $this->timezone);
        if ($parsed === false || $parsed->format('Y-m-d H:i:s.u') !== $wall) {
            throw InvalidPrimitive::for('SQL date-time', 'the wall time does not exist in the configured timezone.');
        }
        if ($this->timezone->getName() !== 'UTC') {
            $at = $parsed->getTimestamp();
            $asUtc = $at + $parsed->getOffset();
            $offsets = array_unique(array_column($this->timezone->getTransitions($at - 86_400, $at + 86_400) ?: [], 'offset'));
            foreach ($offsets as $offset) {
                $candidate = $asUtc - $offset;
                if ($candidate !== $at && (new DateTimeImmutable('@'.$candidate))->setTimezone($this->timezone)->format('Y-m-d H:i:s') === substr($wall, 0, 19)) {
                    throw InvalidPrimitive::for('SQL date-time', 'the wall time is ambiguous in the configured timezone.');
                }
            }
        }

        return DateTimeValue::fromDateTime($parsed);
    }
}
