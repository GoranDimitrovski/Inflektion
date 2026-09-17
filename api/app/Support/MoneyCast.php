<?php

declare(strict_types=1);

namespace App\Support;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;

/**
 * @implements CastsAttributes<Money, Money>
 */
final class MoneyCast implements CastsAttributes
{
    /**
     * @param  array<string, mixed>  $attributes
     */
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
     * @param  array<string, mixed>  $attributes
     * @return array<string, mixed>
     */
    public function set(Model $model, string $key, mixed $value, array $attributes): array
    {
        if ($value === null) {
            return ["{$key}_minor_units" => null, "{$key}_currency" => null];
        }

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
