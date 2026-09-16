<?php

declare(strict_types=1);

namespace App\Commissions;

use RuntimeException;

final class MisconfiguredCommissionException extends RuntimeException
{
    public static function missingRate(int $programId): self
    {
        return new self("Program [{$programId}] uses the percentage commission strategy but has no commission_rate configured.");
    }

    public static function missingFlatAmount(int $programId): self
    {
        return new self("Program [{$programId}] uses the flat commission strategy but has no commission_flat_amount configured.");
    }
}
