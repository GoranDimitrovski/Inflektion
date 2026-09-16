<?php

declare(strict_types=1);

namespace App\Support;

use InvalidArgumentException;

/**
 * Immutable money value object: integer minor units (e.g. cents) + ISO 4217 currency code.
 * No floats, ever — float arithmetic on money is a bug generator.
 */
final class Money
{
    public function __construct(
        public readonly int $minorUnits,
        public readonly string $currency,
    ) {
        if (strlen($currency) !== 3) {
            throw new InvalidArgumentException("Currency code must be a 3-letter ISO 4217 code, got [{$currency}].");
        }
    }

    public static function of(int $minorUnits, string $currency): self
    {
        return new self($minorUnits, strtoupper($currency));
    }

    public function equals(self $other): bool
    {
        return $this->minorUnits === $other->minorUnits && $this->currency === $other->currency;
    }
}
