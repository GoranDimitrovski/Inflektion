<?php

declare(strict_types=1);

namespace App\Commissions\Strategies;

use App\Commissions\CommissionException;
use App\Commissions\CommissionStrategy;
use App\Models\Conversion;
use App\Models\Program;
use App\Support\Money;

final class PercentageCommissionStrategy implements CommissionStrategy
{
    public function calculate(Conversion $conversion, Program $program): Money
    {
        if ($program->commission_rate === null) {
            throw new CommissionException("Program [{$program->id}] uses the percentage commission strategy but has no commission_rate configured.");
        }

        $product = bcmul((string) $conversion->amount->minorUnits, (string) $program->commission_rate, 6);
        $minorUnits = (int) bcround($product, 0);

        return Money::of($minorUnits, $conversion->amount->currency);
    }
}
