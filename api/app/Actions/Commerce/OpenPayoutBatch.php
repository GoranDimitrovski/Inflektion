<?php

declare(strict_types=1);

namespace App\Actions\Commerce;

use App\Access\AuditEntry;
use App\Models\Account;
use App\Models\PayoutBatch;
use App\Models\User;
use App\Support\Clock;
use Illuminate\Support\Facades\DB;

final class OpenPayoutBatch
{
    public function __construct(
        private readonly Clock $clock,
    ) {}

    public function handle(Account $account, ?User $actor = null): PayoutBatch
    {
        return DB::transaction(function () use ($account, $actor): PayoutBatch {
            $now = $this->clock->now();

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
