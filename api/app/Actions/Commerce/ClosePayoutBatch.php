<?php

declare(strict_types=1);

namespace App\Actions\Commerce;

use App\Models\CommissionLedgerEntry;
use App\Models\PayoutBatch;
use App\Support\Clock;
use App\Support\Outbox\OutboxMessage;
use Illuminate\Support\Facades\DB;
use LogicException;

final class ClosePayoutBatch
{
    public function __construct(
        private readonly Clock $clock,
    ) {}

    /**
     * @throws LogicException
     */
    public function handle(PayoutBatch $batch): PayoutBatch
    {
        return DB::transaction(function () use ($batch): PayoutBatch {
            $now = $this->clock->now();

            $batch->close($now);

            // Purely additive: a batch claims entries by inserting rows into the
            // join table, never by updating the ledger — payout_batch_entries has
            // a unique constraint on commission_ledger_entry_id, so an entry can
            // only ever be claimed once.
            $unbatchedEntryIds = CommissionLedgerEntry::query()
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

            return $batch;
        });
    }
}
