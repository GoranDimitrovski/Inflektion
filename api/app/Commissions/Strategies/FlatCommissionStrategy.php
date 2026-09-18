<?php

declare(strict_types=1);

namespace App\Commissions\Strategies;

use App\Commissions\CommissionException;
use App\Commissions\CommissionStrategy;
use App\Models\Conversion;
use App\Models\Program;
use App\Support\Money;

final class FlatCommissionStrategy implements CommissionStrategy
{
    public function calculate(Conversion $conversion, Program $program): Money
    {
        if ($program->commission_flat_amount === null) {
            throw new CommissionException("Program [{$program->id}] uses the flat commission strategy but has no commission_flat_amount configured.");
        }

        return $program->commission_flat_amount;
    }
}
