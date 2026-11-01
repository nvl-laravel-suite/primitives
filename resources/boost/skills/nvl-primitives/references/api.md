# Public API: nvl/primitives

Generated from the package public contract. Use this reference to confirm fully qualified names, parameter order, defaults, native array shapes and return types. Read [usage](usage.md) for policy and operation semantics; declarations alone never grant authority.

Search for the relevant fully qualified service or contract and read only its matching symbol sections; this is a lookup catalog, not an instruction to load every declaration. `resolved` annotations expand imported aliases and domain type shapes. Framework inheritance and host-authored extensions remain outside this package contract.

## Symbols

- `Nvl\Primitives\Casts\ArrayPrimitiveCast`
- `Nvl\Primitives\Casts\MoneyCast`
- `Nvl\Primitives\Contracts\ArrayPrimitive`
- `Nvl\Primitives\Contracts\ExchangeRateProvider`
- `Nvl\Primitives\Contracts\Primitive`
- `Nvl\Primitives\Enums\PrimitivesResponseCode`
- `Nvl\Primitives\Exceptions\ExchangeRateStale`
- `Nvl\Primitives\Exceptions\ExchangeRateUnavailable`
- `Nvl\Primitives\Exceptions\InvalidPrimitive`
- `Nvl\Primitives\Exceptions\PrimitivesException`
- `Nvl\Primitives\Rules\ValidPhoneNumber`
- `Nvl\Primitives\Rules\ValidPrimitive`
- `Nvl\Primitives\Services\ConfiguredExchangeRateProvider`
- `Nvl\Primitives\Services\CurrencyConverter`
- `Nvl\Primitives\Services\MoneyFormatter`
- `Nvl\Primitives\Services\ReferenceCatalog`
- `Nvl\Primitives\ValueObjects\Coordinates`
- `Nvl\Primitives\ValueObjects\CountryCode`
- `Nvl\Primitives\ValueObjects\CurrencyCode`
- `Nvl\Primitives\ValueObjects\DateTimeValue`
- `Nvl\Primitives\ValueObjects\EmailAddress`
- `Nvl\Primitives\ValueObjects\Iban`
- `Nvl\Primitives\ValueObjects\Length`
- `Nvl\Primitives\ValueObjects\LocaleCode`
- `Nvl\Primitives\ValueObjects\Money`
- `Nvl\Primitives\ValueObjects\Percentage`
- `Nvl\Primitives\ValueObjects\PhoneNumber`
- `Nvl\Primitives\ValueObjects\PostalAddress`
- `Nvl\Primitives\ValueObjects\Url`
- `Nvl\Primitives\ValueObjects\Weight`

## Nvl\Primitives\Casts\ArrayPrimitiveCast

```text
final readonly class Nvl\Primitives\Casts\ArrayPrimitiveCast implements Illuminate\Contracts\Database\Eloquent\CastsAttributes
@template TPrimitive of ArrayPrimitive
resolved @template TPrimitive of Nvl\Primitives\Contracts\ArrayPrimitive
@implements CastsAttributes<(TPrimitive | null), (TPrimitive | array<string, mixed> | string | null)>
resolved @implements Illuminate\Contracts\Database\Eloquent\CastsAttributes<(TPrimitive | null), (TPrimitive | array<string, mixed> | string | null)>
template Nvl\Primitives\Casts\ArrayPrimitiveCast::TPrimitive of Nvl\Primitives\Contracts\ArrayPrimitive
public function __construct(string $primitiveClass): mixed
@param class-string<TPrimitive> $primitiveClass
template Nvl\Primitives\Casts\ArrayPrimitiveCast::TPrimitive of Nvl\Primitives\Contracts\ArrayPrimitive
public function get(Illuminate\Database\Eloquent\Model $model, string $key, mixed $value, array $attributes): ?Nvl\Primitives\Contracts\ArrayPrimitive
@param array<string, mixed> $attributes
@return (TPrimitive | null)
@throws JsonException
template Nvl\Primitives\Casts\ArrayPrimitiveCast::TPrimitive of Nvl\Primitives\Contracts\ArrayPrimitive
public function set(Illuminate\Database\Eloquent\Model $model, string $key, mixed $value, array $attributes): ?string
@param array<string, mixed> $attributes
@throws JsonException
```

## Nvl\Primitives\Casts\MoneyCast

```text
final readonly class Nvl\Primitives\Casts\MoneyCast implements Illuminate\Contracts\Database\Eloquent\CastsAttributes
@implements CastsAttributes<(Money | null), (Money | array<string, mixed> | string | int | null)>
resolved @implements Illuminate\Contracts\Database\Eloquent\CastsAttributes<(Nvl\Primitives\ValueObjects\Money | null), (Nvl\Primitives\ValueObjects\Money | array<string, mixed> | string | int | null)>
public function __construct(string $mode=self::Json, ?string $currency=null): mixed
@param string $mode
@param (string | null) $currency
public function get(Illuminate\Database\Eloquent\Model $model, string $key, mixed $value, array $attributes): ?Nvl\Primitives\ValueObjects\Money
@param array<string, mixed> $attributes
@throws JsonException
public function set(Illuminate\Database\Eloquent\Model $model, string $key, mixed $value, array $attributes): ?string
@param array<string, mixed> $attributes
@throws JsonException
```

## Nvl\Primitives\Contracts\ArrayPrimitive

```text
interface Nvl\Primitives\Contracts\ArrayPrimitive extends Nvl\Primitives\Contracts\Primitive
public abstract function equals(Nvl\Primitives\Contracts\Primitive $other): bool
public abstract static function fromArray(array $value): static
@param array<string, mixed> $value
public abstract function toArray(): array
@return array<string, mixed>
```

## Nvl\Primitives\Contracts\ExchangeRateProvider

```text
interface Nvl\Primitives\Contracts\ExchangeRateProvider
public abstract function rate(Nvl\Primitives\ValueObjects\CurrencyCode $from, Nvl\Primitives\ValueObjects\CurrencyCode $to, ?DateTimeInterface $at=null): string
```

## Nvl\Primitives\Contracts\Primitive

```text
interface Nvl\Primitives\Contracts\Primitive extends Illuminate\Contracts\Database\Eloquent\Castable, JsonSerializable, Stringable
public abstract function equals(Nvl\Primitives\Contracts\Primitive $other): bool
```

## Nvl\Primitives\Enums\PrimitivesResponseCode

```text
enum Nvl\Primitives\Enums\PrimitivesResponseCode implements BackedEnum, Nvl\Support\Contracts\ResponseCode, UnitEnum: string
public const Nvl\Primitives\Enums\PrimitivesResponseCode ExchangeRateStale=Nvl\Primitives\Enums\PrimitivesResponseCode::ExchangeRateStale="exchange_rate_stale"
public const Nvl\Primitives\Enums\PrimitivesResponseCode ExchangeRateUnavailable=Nvl\Primitives\Enums\PrimitivesResponseCode::ExchangeRateUnavailable="exchange_rate_unavailable"
public const Nvl\Primitives\Enums\PrimitivesResponseCode OperationFailed=Nvl\Primitives\Enums\PrimitivesResponseCode::OperationFailed="operation_failed"
public readonly string $name
public static function cases(): array
public readonly string $value
public static function from(string|int $value): static
public static function tryFrom(string|int $value): ?static
```

## Nvl\Primitives\Exceptions\ExchangeRateStale

```text
final class Nvl\Primitives\Exceptions\ExchangeRateStale extends Nvl\Primitives\Exceptions\PrimitivesException
public static function forPair(string $pair): self
public function package(): string
public function publicContext(): array
@return array<string, mixed>
public function responseCode(): ?string
public function responseHeaders(): array
@return array<string, string>
public function suggestedStatus(): int
public function translationKey(): ?string
public function translationParameters(): array
@return array<string, (scalar | null)>
```

## Nvl\Primitives\Exceptions\ExchangeRateUnavailable

```text
final class Nvl\Primitives\Exceptions\ExchangeRateUnavailable extends Nvl\Primitives\Exceptions\PrimitivesException
public static function between(string $from, string $to): self
public function package(): string
public function publicContext(): array
@return array<string, mixed>
public function responseCode(): ?string
public function responseHeaders(): array
@return array<string, string>
public function suggestedStatus(): int
public function translationKey(): ?string
public function translationParameters(): array
@return array<string, (scalar | null)>
```

## Nvl\Primitives\Exceptions\InvalidPrimitive

```text
final class Nvl\Primitives\Exceptions\InvalidPrimitive extends InvalidArgumentException implements Nvl\Support\Contracts\PackageException
public static function for(string $primitive, string $reason, ?Throwable $previous=null): self
```

## Nvl\Primitives\Exceptions\PrimitivesException

```text
class Nvl\Primitives\Exceptions\PrimitivesException extends RuntimeException implements Nvl\Support\Contracts\RespondableException
public function package(): string
public function publicContext(): array
@return array<string, mixed>
public function responseCode(): ?string
public function responseHeaders(): array
@return array<string, string>
public function suggestedStatus(): int
public function translationKey(): ?string
public function translationParameters(): array
@return array<string, (scalar | null)>
```

## Nvl\Primitives\Rules\ValidPhoneNumber

```text
final readonly class Nvl\Primitives\Rules\ValidPhoneNumber implements Illuminate\Contracts\Validation\ValidationRule
public function __construct(?string $region=null): mixed
public function validate(string $attribute, mixed $value, Closure $fail): void
@param Closure(string, ?string=): PotentiallyTranslatedString $fail
resolved @param Closure(string, ?string=): Illuminate\Translation\PotentiallyTranslatedString $fail
```

## Nvl\Primitives\Rules\ValidPrimitive

```text
final readonly class Nvl\Primitives\Rules\ValidPrimitive implements Illuminate\Contracts\Validation\ValidationRule
public function __construct(string $primitiveClass, string $label="value"): mixed
@param class-string $primitiveClass
public function validate(string $attribute, mixed $value, Closure $fail): void
@param Closure(string, ?string=): PotentiallyTranslatedString $fail
resolved @param Closure(string, ?string=): Illuminate\Translation\PotentiallyTranslatedString $fail
```

## Nvl\Primitives\Services\ConfiguredExchangeRateProvider

```text
final readonly class Nvl\Primitives\Services\ConfiguredExchangeRateProvider implements Nvl\Primitives\Contracts\ExchangeRateProvider
public function __construct(Illuminate\Contracts\Config\Repository $config): mixed
public function rate(Nvl\Primitives\ValueObjects\CurrencyCode $from, Nvl\Primitives\ValueObjects\CurrencyCode $to, ?DateTimeInterface $at=null): string
```

## Nvl\Primitives\Services\CurrencyConverter

```text
final readonly class Nvl\Primitives\Services\CurrencyConverter
public function __construct(Nvl\Primitives\Contracts\ExchangeRateProvider $rates): mixed
public function convert(Nvl\Primitives\ValueObjects\Money $money, Nvl\Primitives\ValueObjects\CurrencyCode|string $target, Brick\Math\RoundingMode $roundingMode, ?DateTimeInterface $at=null): Nvl\Primitives\ValueObjects\Money
```

## Nvl\Primitives\Services\MoneyFormatter

```text
final readonly class Nvl\Primitives\Services\MoneyFormatter
public function __construct(Illuminate\Contracts\Config\Repository $config): mixed
public function format(Nvl\Primitives\ValueObjects\Money $money, ?string $locale=null): string
```

## Nvl\Primitives\Services\ReferenceCatalog

```text
final readonly class Nvl\Primitives\Services\ReferenceCatalog
public function __construct(Illuminate\Contracts\Config\Repository $config, Nvl\Support\Contracts\LocaleCatalog $locales): mixed
public function banks(?string $search=null, int $limit=50): array
@return list<array{code: string, label: string, metadata: array<string, (bool | int | float | string | null)>}>
public function cities(?string $search=null, int $limit=50): array
@return list<array{code: string, label: string, metadata: array<string, (bool | int | float | string | null)>}>
public function countries(?string $search=null, ?string $displayLocale=null, int $limit=50): array
@return list<array{code: string, label: string, metadata: array<string, (bool | int | float | string | null)>}>
public function currencies(?string $search=null, ?string $displayLocale=null, int $limit=50): array
@return list<array{code: string, label: string, metadata: array<string, (bool | int | float | string | null)>}>
public function languages(?string $search=null, ?string $displayLocale=null, int $limit=50): array
@return list<array{code: string, label: string, metadata: array<string, (bool | int | float | string | null)>}>
public function locales(?string $search=null, ?string $displayLocale=null, int $limit=50): array
@return list<array{code: string, label: string, metadata: array<string, (bool | int | float | string | null)>}>
```

## Nvl\Primitives\ValueObjects\Coordinates

```text
final readonly class Nvl\Primitives\ValueObjects\Coordinates implements Nvl\Primitives\Contracts\ArrayPrimitive
public function __toString(): string
public static function castUsing(array $arguments): Illuminate\Contracts\Database\Eloquent\CastsAttributes
@param list<string> $arguments
@return ArrayPrimitiveCast<static>
resolved @return Nvl\Primitives\Casts\ArrayPrimitiveCast<Nvl\Primitives\ValueObjects\Coordinates>
public function distanceTo(self $other): float
public function equals(Nvl\Primitives\Contracts\Primitive $other): bool
public static function from(string|int|float $latitude, string|int|float $longitude): self
public static function fromArray(array $value): static
@param array<string, mixed> $value
public static function fromString(string $value): self
public function googleMapsUrl(): Nvl\Primitives\ValueObjects\Url
public function jsonSerialize(): array
@return array{latitude: string, longitude: string}
public function latitude(): string
public function longitude(): string
public function toArray(): array
@return array{latitude: string, longitude: string}
```

## Nvl\Primitives\ValueObjects\CountryCode

```text
final readonly class Nvl\Primitives\ValueObjects\CountryCode implements Nvl\Primitives\Contracts\ScalarPrimitive
public function __toString(): string
public static function castUsing(array $arguments): Illuminate\Contracts\Database\Eloquent\CastsAttributes
@param list<string> $arguments
@return CastsAttributes<(static | null), (static | string | int | null)>
resolved @return Illuminate\Contracts\Database\Eloquent\CastsAttributes<(Nvl\Primitives\ValueObjects\CountryCode | null), (Nvl\Primitives\ValueObjects\CountryCode | string | int | null)>
public function equals(Nvl\Primitives\Contracts\Primitive $other): bool
public static function from(string $value): static
public function jsonSerialize(): string
public function name(?string $displayLocale=null): string
public function storageValue(): string
public static function tryFrom(string $value): ?self
```

## Nvl\Primitives\ValueObjects\CurrencyCode

```text
final readonly class Nvl\Primitives\ValueObjects\CurrencyCode implements Nvl\Primitives\Contracts\ScalarPrimitive
public function __toString(): string
public static function castUsing(array $arguments): Illuminate\Contracts\Database\Eloquent\CastsAttributes
@param list<string> $arguments
@return CastsAttributes<(static | null), (static | string | int | null)>
resolved @return Illuminate\Contracts\Database\Eloquent\CastsAttributes<(Nvl\Primitives\ValueObjects\CurrencyCode | null), (Nvl\Primitives\ValueObjects\CurrencyCode | string | int | null)>
public function equals(Nvl\Primitives\Contracts\Primitive $other): bool
public function fractionDigits(): int
public static function from(string $value): static
public function jsonSerialize(): string
public function name(?string $displayLocale=null): string
public function storageValue(): string
public function symbol(?string $displayLocale=null): string
public function toBrick(): Brick\Money\Currency
public static function tryFrom(string $value): ?self
```

## Nvl\Primitives\ValueObjects\DateTimeValue

```text
final readonly class Nvl\Primitives\ValueObjects\DateTimeValue implements Nvl\Primitives\Contracts\ScalarPrimitive
public function __toString(): string
public function carbon(): Carbon\CarbonImmutable
public static function castUsing(array $arguments): Illuminate\Contracts\Database\Eloquent\CastsAttributes
@param list<string> $arguments
@return CastsAttributes<(static | null), (static | string | int | null)>
resolved @return Illuminate\Contracts\Database\Eloquent\CastsAttributes<(Nvl\Primitives\ValueObjects\DateTimeValue | null), (Nvl\Primitives\ValueObjects\DateTimeValue | string | int | null)>
public function equals(Nvl\Primitives\Contracts\Primitive $other): bool
public static function from(string $value): static
public static function fromDateTime(DateTimeInterface $value): self
public function inTimezone(string $timezone): Carbon\CarbonImmutable
public function jsonSerialize(): string
public static function now(): self
public function storageValue(): string
```

## Nvl\Primitives\ValueObjects\EmailAddress

```text
final readonly class Nvl\Primitives\ValueObjects\EmailAddress implements Nvl\Primitives\Contracts\ScalarPrimitive
public function __toString(): string
public static function castUsing(array $arguments): Illuminate\Contracts\Database\Eloquent\CastsAttributes
@param list<string> $arguments
@return CastsAttributes<(static | null), (static | string | int | null)>
resolved @return Illuminate\Contracts\Database\Eloquent\CastsAttributes<(Nvl\Primitives\ValueObjects\EmailAddress | null), (Nvl\Primitives\ValueObjects\EmailAddress | string | int | null)>
public function domain(): string
public function equals(Nvl\Primitives\Contracts\Primitive $other): bool
public static function from(string $value): static
public function jsonSerialize(): string
public function localPart(): string
public function masked(): string
public function storageValue(): string
public static function tryFrom(string $value): ?self
```

## Nvl\Primitives\ValueObjects\Iban

```text
final readonly class Nvl\Primitives\ValueObjects\Iban implements Nvl\Primitives\Contracts\ScalarPrimitive
public function __toString(): string
public function bankIdentifier(): string
public function bban(): string
public static function castUsing(array $arguments): Illuminate\Contracts\Database\Eloquent\CastsAttributes
@param list<string> $arguments
@return CastsAttributes<(static | null), (static | string | int | null)>
resolved @return Illuminate\Contracts\Database\Eloquent\CastsAttributes<(Nvl\Primitives\ValueObjects\Iban | null), (Nvl\Primitives\ValueObjects\Iban | string | int | null)>
public function checksum(): string
public function country(): Nvl\Primitives\ValueObjects\CountryCode
public function equals(Nvl\Primitives\Contracts\Primitive $other): bool
public function formatted(): string
public static function from(string $value): static
public function jsonSerialize(): string
public function masked(): string
public function storageValue(): string
public static function tryFrom(string $value): ?self
```

## Nvl\Primitives\ValueObjects\Length

```text
final readonly class Nvl\Primitives\ValueObjects\Length implements Nvl\Primitives\Contracts\ArrayPrimitive
public function __toString(): string
public static function castUsing(array $arguments): Illuminate\Contracts\Database\Eloquent\CastsAttributes
@param list<string> $arguments
@return ArrayPrimitiveCast<static>
resolved @return Nvl\Primitives\Casts\ArrayPrimitiveCast<Nvl\Primitives\ValueObjects\Length>
public function equals(Nvl\Primitives\Contracts\Primitive $other): bool
public static function from(string|int $value, string $unit="m"): self
public static function fromArray(array $value): static
@param array<string, mixed> $value
public function in(string $unit, int $scale, Brick\Math\RoundingMode $roundingMode): string
public function jsonSerialize(): array
@return array{value: string, unit: string}
public function toArray(): array
@return array{value: string, unit: string}
```

## Nvl\Primitives\ValueObjects\LocaleCode

```text
final readonly class Nvl\Primitives\ValueObjects\LocaleCode implements Nvl\Primitives\Contracts\ScalarPrimitive
public function __toString(): string
public static function castUsing(array $arguments): Illuminate\Contracts\Database\Eloquent\CastsAttributes
@param list<string> $arguments
@return CastsAttributes<(static | null), (static | string | int | null)>
resolved @return Illuminate\Contracts\Database\Eloquent\CastsAttributes<(Nvl\Primitives\ValueObjects\LocaleCode | null), (Nvl\Primitives\ValueObjects\LocaleCode | string | int | null)>
public function equals(Nvl\Primitives\Contracts\Primitive $other): bool
public static function from(string $value): static
public function jsonSerialize(): string
public function language(): string
public function region(): ?Nvl\Primitives\ValueObjects\CountryCode
public function regionCode(): ?string
public function script(): ?string
public function storageValue(): string
public static function tryFrom(string $value): ?self
```

## Nvl\Primitives\ValueObjects\Money

```text
final readonly class Nvl\Primitives\ValueObjects\Money implements Nvl\Primitives\Contracts\ArrayPrimitive
public function __toString(): string
public function absolute(): self
public function add(self $other): self
public function allocate(array $ratios): array
@param list<(string | int)> $ratios
@return list<self>
resolved @return list<Nvl\Primitives\ValueObjects\Money>
public function amount(): string
public static function castUsing(array $arguments): Illuminate\Contracts\Database\Eloquent\CastsAttributes
@param list<string> $arguments
@return MoneyCast
resolved @return Nvl\Primitives\Casts\MoneyCast
public function compare(self $other): int
public function convert(Nvl\Primitives\ValueObjects\CurrencyCode|string $currency, string $rate, Brick\Math\RoundingMode $roundingMode): self
public function currency(): Nvl\Primitives\ValueObjects\CurrencyCode
public function divide(string|int $divisor, ?Brick\Math\RoundingMode $roundingMode=null): self
public function equals(Nvl\Primitives\Contracts\Primitive $other): bool
public static function fromArray(array $value): static
@param array<string, mixed> $value
public function isNegative(): bool
public function isPositive(): bool
public function isZero(): bool
public function jsonSerialize(): array
@return array{minor: string, currency: string}
public static function minor(string|int $minorAmount, Nvl\Primitives\ValueObjects\CurrencyCode|string $currency): self
public function minorAmount(): string
public function multiply(string|int $multiplier, ?Brick\Math\RoundingMode $roundingMode=null): self
public function negate(): self
public static function of(string|int $amount, Nvl\Primitives\ValueObjects\CurrencyCode|string $currency, ?Brick\Math\RoundingMode $roundingMode=null): self
public function split(int $parts): array
@return list<self>
resolved @return list<Nvl\Primitives\ValueObjects\Money>
public function subtract(self $other): self
public function toArray(): array
@return array{minor: string, currency: string}
public function toBrick(): Brick\Money\Money
public static function zero(Nvl\Primitives\ValueObjects\CurrencyCode|string $currency): self
```

## Nvl\Primitives\ValueObjects\Percentage

```text
final readonly class Nvl\Primitives\ValueObjects\Percentage implements Nvl\Primitives\Contracts\ScalarPrimitive
public function __toString(): string
public function add(self $other): self
public static function castUsing(array $arguments): Illuminate\Contracts\Database\Eloquent\CastsAttributes
@param list<string> $arguments
@return CastsAttributes<(static | null), (static | string | int | null)>
resolved @return Illuminate\Contracts\Database\Eloquent\CastsAttributes<(Nvl\Primitives\ValueObjects\Percentage | null), (Nvl\Primitives\ValueObjects\Percentage | string | int | null)>
public function decimal(): string
public function equals(Nvl\Primitives\Contracts\Primitive $other): bool
public static function from(string $value): static
public static function fromDecimal(string|int $value): self
public static function fromPercent(string|int $value): self
public static function full(): self
public function isNormalized(): bool
public function jsonSerialize(): string
public function of(string|int $amount): string
public function ofRounded(string|int $amount, int $scale, Brick\Math\RoundingMode $roundingMode): string
public function percent(): string
public function percentRounded(int $scale, Brick\Math\RoundingMode $roundingMode): string
public function storageValue(): string
public function subtract(self $other): self
public static function zero(): self
```

## Nvl\Primitives\ValueObjects\PhoneNumber

```text
final readonly class Nvl\Primitives\ValueObjects\PhoneNumber implements Nvl\Primitives\Contracts\ScalarPrimitive
public function __toString(): string
public static function castUsing(array $arguments): Illuminate\Contracts\Database\Eloquent\CastsAttributes
@param list<string> $arguments
@return CastsAttributes<(static | null), (static | string | int | null)>
resolved @return Illuminate\Contracts\Database\Eloquent\CastsAttributes<(Nvl\Primitives\ValueObjects\PhoneNumber | null), (Nvl\Primitives\ValueObjects\PhoneNumber | string | int | null)>
public function equals(Nvl\Primitives\Contracts\Primitive $other): bool
public static function from(string $value): static
public static function fromRegion(string $value, ?string $region): self
public function international(): string
public function jsonSerialize(): string
public function national(): string
public function region(): ?Nvl\Primitives\ValueObjects\CountryCode
public function rfc3966(): string
public function storageValue(): string
public static function tryFrom(string $value, ?string $region=null): ?self
```

## Nvl\Primitives\ValueObjects\PostalAddress

```text
final readonly class Nvl\Primitives\ValueObjects\PostalAddress implements Nvl\Primitives\Contracts\ArrayPrimitive
public function __construct(string $line1, ?string $line2, string $locality, ?string $administrativeArea, ?string $postalCode, Nvl\Primitives\ValueObjects\CountryCode $country): mixed
public function __toString(): string
public static function castUsing(array $arguments): Illuminate\Contracts\Database\Eloquent\CastsAttributes
@param list<string> $arguments
@return ArrayPrimitiveCast<static>
resolved @return Nvl\Primitives\Casts\ArrayPrimitiveCast<Nvl\Primitives\ValueObjects\PostalAddress>
public function equals(Nvl\Primitives\Contracts\Primitive $other): bool
public static function fromArray(array $value): static
@param array<string, mixed> $value
public function jsonSerialize(): array
@return array{line1: string, line2: (string | null), locality: string, administrativeArea: (string | null), postalCode: (string | null), country: string}
public function toArray(): array
@return array{line1: string, line2: (string | null), locality: string, administrativeArea: (string | null), postalCode: (string | null), country: string}
public readonly ?string $administrativeArea
public readonly Nvl\Primitives\ValueObjects\CountryCode $country
public readonly string $line1
public readonly ?string $line2
public readonly string $locality
public readonly ?string $postalCode
```

## Nvl\Primitives\ValueObjects\Url

```text
final readonly class Nvl\Primitives\ValueObjects\Url implements Nvl\Primitives\Contracts\ScalarPrimitive
public function __toString(): string
public static function castUsing(array $arguments): Illuminate\Contracts\Database\Eloquent\CastsAttributes
@param list<string> $arguments
@return CastsAttributes<(static | null), (static | string | int | null)>
resolved @return Illuminate\Contracts\Database\Eloquent\CastsAttributes<(Nvl\Primitives\ValueObjects\Url | null), (Nvl\Primitives\ValueObjects\Url | string | int | null)>
public function equals(Nvl\Primitives\Contracts\Primitive $other): bool
public static function from(string $value): static
public function host(): string
public function isSecure(): bool
public function jsonSerialize(): string
public function scheme(): string
public function storageValue(): string
public static function tryFrom(string $value): ?self
```

## Nvl\Primitives\ValueObjects\Weight

```text
final readonly class Nvl\Primitives\ValueObjects\Weight implements Nvl\Primitives\Contracts\ScalarPrimitive
public function __toString(): string
public function add(self $other): self
public static function castUsing(array $arguments): Illuminate\Contracts\Database\Eloquent\CastsAttributes
@param list<string> $arguments
@return CastsAttributes<(static | null), (static | string | int | null)>
resolved @return Illuminate\Contracts\Database\Eloquent\CastsAttributes<(Nvl\Primitives\ValueObjects\Weight | null), (Nvl\Primitives\ValueObjects\Weight | string | int | null)>
public function equals(Nvl\Primitives\Contracts\Primitive $other): bool
public static function from(string $value): static
public static function grams(string|int $value): self
public function inGrams(int $scale=3): string
public function inKilograms(int $scale=3): string
public function inOunces(int $scale=3): string
public function inPounds(int $scale=3): string
public function jsonSerialize(): string
public static function kilograms(string|int $value): self
public static function ounces(string|int $value): self
public static function pounds(string|int $value): self
public function storageValue(): string
public function subtract(self $other): self
```
