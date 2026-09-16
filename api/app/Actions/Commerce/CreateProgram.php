<?php

declare(strict_types=1);

namespace App\Actions\Commerce;

use App\Models\Program;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;

final class CreateProgram
{
    /**
     * @throws UniqueConstraintViolationException
     */
    public function handle(CreateProgramData $data): Program
    {
        return DB::transaction(fn (): Program => Program::create([
            'name' => $data->name,
            'slug' => $data->slug,
            'status' => $data->status ?? 'draft',
            'commission_strategy' => $data->commissionStrategy,
            'commission_rate' => $data->commissionRate,
            'commission_flat_amount' => $data->commissionFlatAmount,
        ]));
    }
}
