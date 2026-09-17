<?php

declare(strict_types=1);

namespace App\Actions\Commerce;

use App\Access\AuditEntry;
use App\Models\CommissionLedgerEntry;
use App\Models\PayoutBatch;
use App\Models\User;
use App\Support\Clock;
use App\Support\Outbox\OutboxMessage;
use Illuminate\Support\Facades\DB;

final class ClosePayoutBatch
{
    public function __construct(
        private readonly Clock $clock,
    ) {}

    public function handle(PayoutBatch $batch, ?User $actor = null): PayoutBatch
    {
        return DB::transaction(function () use ($batch, $actor): PayoutBatch {
            $now = $this->clock->now();

            $batch->close($now);

            $unbatchedEntryIds = CommissionLedgerEntry::query()
                ->where('account_id', $batch->account_id)
                ->where('created_at', '>=', $batch->opened_at)
                ->whereNotIn('id', DB::table('payout_batch_entries')->select('commission_ledger_entry_id'))
                ->pluck('id');

            if ($unbatchedEntryIds->isNotEmpty()) {
                DB::table('payout_batch_entries')->insert(
                    $unbatchedEntryIds->map(fn (int $entryId): array => [
                        'payout_batch_id' => $batch->id,
                        'commission_ledger_entry_id' => $entryId,
                        'created_at' => $now,
                    ])->all()
                );
            }

            OutboxMessage::create([
                'aggregate_type' => 'payout_batch',
                'aggregate_id' => (string) $batch->id,
                'event_type' => 'PayoutBatchClosed',
                'payload' => [
                    'payout_batch_id' => $batch->id,
                    'closed_at' => $batch->closed_at?->format(DATE_ATOM),
                ],
                'created_at' => $now,
            ]);

            AuditEntry::record($batch->account_id, $actor?->id, 'commerce.payout_batch_closed', $batch, [], $now);

            return $batch;
        });
    }
}
