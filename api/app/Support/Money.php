<?php

declare(strict_types=1);

namespace App\Support;

use InvalidArgumentException;

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
