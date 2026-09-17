<?php

declare(strict_types=1);

namespace App\Actions\Commerce;

use App\Access\AuditEntry;
use App\Access\TenantContext;
use App\Models\Program;
use App\Models\User;
use Illuminate\Support\Facades\DB;

final class CreateProgram
{
    public function __construct(
        private readonly TenantContext $tenant,
    ) {}

    public function handle(CreateProgramData $data, User $actor): Program
    {
        return DB::transaction(function () use ($data, $actor): Program {
            $account = $this->tenant->account();

            $program = Program::create([
                'account_id' => $account->id,
                'name' => $data->name,
                'slug' => $data->slug,
                'status' => $data->status ?? 'draft',
                'commission_strategy' => $data->commissionStrategy,
                'commission_rate' => $data->commissionRate,
                'commission_flat_amount' => $data->commissionFlatAmount,
            ]);

            if ($data->commissionStrategy !== null) {
                AuditEntry::record($account->id, $actor->id, 'commerce.commission_strategy_assigned', $program, [
                    'strategy' => $data->commissionStrategy,
                ], now());
            }

            return $program;
        });
    }
}
