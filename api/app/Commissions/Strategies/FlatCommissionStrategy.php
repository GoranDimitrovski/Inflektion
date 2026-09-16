<?php

declare(strict_types=1);

namespace App\Commissions\Strategies;

use App\Commissions\CommissionStrategy;
use App\Commissions\MisconfiguredCommissionException;
use App\Models\Conversion;
use App\Models\Program;
use App\Support\Money;

final class FlatCommissionStrategy implements CommissionStrategy
{
    public function calculate(Conversion $conversion, Program $program): Money
    {
        if ($program->commission_flat_amount === null) {
            throw MisconfiguredCommissionException::missingFlatAmount($program->id);
        }

        return $program->commission_flat_amount;
    }
}
