<?php

declare(strict_types=1);

namespace App\Commissions;

use App\Models\Conversion;
use App\Models\Program;
use App\Support\Money;

interface CommissionStrategy
{
    public function calculate(Conversion $conversion, Program $program): Money;
}
