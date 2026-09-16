<?php

declare(strict_types=1);

namespace App\Actions\Commerce;

use App\Commissions\CommissionStrategyRegistry;
use App\Models\CommissionLedgerEntry;
use App\Models\Conversion;
use App\Support\Clock;
use App\Support\Outbox\OutboxMessage;
use Illuminate\Support\Facades\DB;
use LogicException;

final class WriteCommission
{
    public function __construct(
        private readonly CommissionStrategyRegistry $registry,
        private readonly Clock $clock,
    ) {}

    /**
     * @throws LogicException
     */
    public function handle(Conversion $conversion): CommissionLedgerEntry
    {
        if ($conversion->attributed_program_id === null) {
            throw new LogicException("Cannot write commission for conversion [{$conversion->id}]: it has no attributed program.");
        }

        return DB::transaction(function () use ($conversion): CommissionLedgerEntry {
            $now = $this->clock->now();

            $program = $conversion->attributedProgram()->firstOrFail();

            $strategy = $this->registry->resolve($program->commission_strategy);
            $amount = $strategy->calculate($conversion, $program);

            $entry = CommissionLedgerEntry::create([
                'conversion_id' => $conversion->id,
                'program_id' => $program->id,
                'amount' => $amount,
                'type' => 'commission',
                'created_at' => $now,
            ]);

            OutboxMessage::create([
                'aggregate_type' => 'commission_ledger_entry',
                'aggregate_id' => (string) $entry->id,
                'event_type' => 'CommissionWritten',
                'payload' => [
                    'commission_ledger_entry_id' => $entry->id,
                    'conversion_id' => $conversion->id,
                    'program_id' => $program->id,
                    'amount_minor_units' => $amount->minorUnits,
                    'amount_currency' => $amount->currency,
                ],
                'created_at' => $now,
            ]);

            return $entry;
        });
    }
}
