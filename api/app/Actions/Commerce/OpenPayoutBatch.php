<?php

declare(strict_types=1);

namespace App\Actions\Commerce;

use App\Access\AuditEntry;
use App\Models\Account;
use App\Models\PayoutBatch;
use App\Models\User;
use Illuminate\Support\Facades\DB;

final class OpenPayoutBatch
{
    public function handle(Account $account, ?User $actor = null): PayoutBatch
    {
        return DB::transaction(function () use ($account, $actor): PayoutBatch {
            $now = now();

            $batch = PayoutBatch::create([
                'account_id' => $account->id,
                'status' => PayoutBatch::STATUS_OPEN,
                'opened_at' => $now,
            ]);

            AuditEntry::record($account->id, $actor?->id, 'commerce.payout_batch_opened', $batch, [], $now);

            return $batch;
        });
    }
}
