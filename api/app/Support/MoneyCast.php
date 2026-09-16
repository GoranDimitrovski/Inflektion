<?php

declare(strict_types=1);

namespace App\Support;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;

// Casts to/from two columns ({attribute}_minor_units, {attribute}_currency) so both stay queryable/indexable.
/**
 * @implements CastsAttributes<Money, Money>
 */
final class MoneyCast implements CastsAttributes
{
    public function get(Model $model, string $key, mixed $value, array $attributes): ?Money
    {
        $minorUnits = $attributes["{$key}_minor_units"] ?? null;
        $currency = $attributes["{$key}_currency"] ?? null;

        if ($minorUnits === null || $currency === null) {
            return null;
        }

        return new Money((int) $minorUnits, (string) $currency);
    }

    /**
     * @return array<string, int|string|null>
     */
    public function set(Model $model, string $key, mixed $value, array $attributes): array
    {
        if ($value === null) {
            return ["{$key}_minor_units" => null, "{$key}_currency" => null];
        }

        // @phpstan-ignore instanceof.alwaysTrue (runtime guard for callers that bypass static analysis)
        if (! $value instanceof Money) {
            throw new InvalidArgumentException(sprintf(
                'Expected %s to be an instance of %s, got %s.',
                $key,
                Money::class,
                get_debug_type($value),
            ));
        }

        return [
            "{$key}_minor_units" => $value->minorUnits,
            "{$key}_currency" => $value->currency,
        ];
    }
}
