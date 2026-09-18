<?php

declare(strict_types=1);

namespace Tests\Unit\Actions\Commerce;

use App\Actions\Commerce\WriteCommission;
use App\Models\CommissionLedgerEntry;
use App\Models\Conversion;
use App\Models\Program;
use App\Support\Money;
use App\Support\Outbox\OutboxMessage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use LogicException;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class WriteCommissionTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function itWritesACommissionLedgerEntryAndAnOutboxMessageForAnAttributedConversion(): void
    {
        $program = Program::factory()->create([
            'commission_strategy' => 'percentage',
            'commission_rate' => '0.1000',
        ]);

        $conversion = Conversion::create([
            'account_id' => $program->account_id,
            'vendor' => 'demo-store',
            'external_id' => 'write-commission-flow',
            'amount' => Money::of(10000, 'USD'),
            'status' => 'attributed',
            'attributed_program_id' => $program->id,
        ]);

        $entry = app(WriteCommission::class)->handle($conversion);

        $this->assertInstanceOf(CommissionLedgerEntry::class, $entry);
        $this->assertEquals(Money::of(1000, 'USD'), $entry->amount);
        $this->assertSame($conversion->id, $entry->conversion_id);
        $this->assertSame($program->id, $entry->program_id);
        $this->assertSame($program->account_id, $entry->account_id);

        $this->assertDatabaseHas('commission_ledger_entries', [
            'account_id' => $program->account_id,
            'conversion_id' => $conversion->id,
            'program_id' => $program->id,
            'amount_minor_units' => 1000,
            'amount_currency' => 'USD',
        ]);

        $message = OutboxMessage::query()
            ->where('aggregate_type', 'commission_ledger_entry')
            ->where('aggregate_id', (string) $entry->id)
            ->first();

        $this->assertNotNull($message);
        $this->assertSame('CommissionWritten', $message->event_type);
        $this->assertNull($message->published_at);
        $this->assertSame(1000, $message->payload['amount_minor_units']);
    }

    #[Test]
    public function itThrowsForAConversionThatHasNotBeenAttributedToAProgram(): void
    {
        $conversion = Conversion::create([
            'vendor' => 'demo-store',
            'external_id' => 'unattributed',
            'amount' => Money::of(1000, 'USD'),
            'status' => 'recorded',
        ]);

        $this->expectException(LogicException::class);

        try {
            app(WriteCommission::class)->handle($conversion);
        } finally {
            $this->assertDatabaseCount('commission_ledger_entries', 0);
            $this->assertDatabaseCount('outbox_messages', 0);
        }
    }
}
