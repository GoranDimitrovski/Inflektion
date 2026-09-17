<?php

declare(strict_types=1);

namespace App\Commissions\Strategies;

use App\Commissions\CommissionStrategy;
use App\Commissions\MisconfiguredCommissionException;
use App\Models\Conversion;
use App\Models\Program;
use App\Support\Money;

final class PercentageCommissionStrategy implements CommissionStrategy
{
    public function calculate(Conversion $conversion, Program $program): Money
    {
        if ($program->commission_rate === null) {
            throw MisconfiguredCommissionException::missingRate($program->id);
        }

        $product = bcmul((string) $conversion->amount->minorUnits, (string) $program->commission_rate, 6);
        $minorUnits = (int) bcround($product, 0);

        return Money::of($minorUnits, $conversion->amount->currency);
    }
}
