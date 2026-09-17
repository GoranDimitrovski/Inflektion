<?php

declare(strict_types=1);

namespace Tests\Unit\Actions\Commerce;

use App\Actions\Commerce\ClosePayoutBatch;
use App\Actions\Commerce\OpenPayoutBatch;
use App\Models\Account;
use App\Models\CommissionLedgerEntry;
use App\Models\Conversion;
use App\Models\PayoutBatch;
use App\Models\Program;
use App\Support\Money;
use App\Support\Outbox\OutboxMessage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use LogicException;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class ClosePayoutBatchTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function itClosesAnOpenBatchAssignsUnbatchedLedgerEntriesToItAndWritesAnOutboxMessage(): void
    {
        $account = Account::factory()->create();
        $batch = app(OpenPayoutBatch::class)->handle($account);

        $program = Program::factory()->create(['account_id' => $account->id]);
        $conversion = Conversion::create([
            'account_id' => $account->id,
            'vendor' => 'demo-store',
            'external_id' => 'close-batch-flow',
            'amount' => Money::of(1000, 'USD'),
            'status' => 'attributed',
            'attributed_program_id' => $program->id,
        ]);

        $entry = CommissionLedgerEntry::create([
            'account_id' => $account->id,
            'conversion_id' => $conversion->id,
            'program_id' => $program->id,
            'amount' => Money::of(100, 'USD'),
            'type' => 'commission',
            'created_at' => now(),
        ]);

        $closed = app(ClosePayoutBatch::class)->handle($batch);

        $this->assertSame(PayoutBatch::STATUS_CLOSED, $closed->status);

        $this->assertTrue(DB::table('payout_batch_entries')
            ->where('payout_batch_id', $batch->id)
            ->where('commission_ledger_entry_id', $entry->id)
            ->exists());

        $message = OutboxMessage::query()
            ->where('aggregate_type', 'payout_batch')
            ->where('aggregate_id', (string) $batch->id)
            ->first();

        $this->assertNotNull($message);
        $this->assertSame('PayoutBatchClosed', $message->event_type);

        $this->assertDatabaseHas('audit_entries', [
            'account_id' => $account->id,
            'action' => 'commerce.payout_batch_opened',
            'subject_id' => $batch->id,
        ]);
        $this->assertDatabaseHas('audit_entries', [
            'account_id' => $account->id,
            'action' => 'commerce.payout_batch_closed',
            'subject_id' => $batch->id,
        ]);
    }

    #[Test]
    public function itNeverClaimsAnotherAccountsCommissionLedgerEntries(): void
    {
        $accountA = Account::factory()->create();
        $accountB = Account::factory()->create();
        $batchA = app(OpenPayoutBatch::class)->handle($accountA);

        $programB = Program::factory()->create(['account_id' => $accountB->id]);
        $conversionB = Conversion::create([
            'account_id' => $accountB->id,
            'vendor' => 'demo-store',
            'external_id' => 'other-account-entry',
            'amount' => Money::of(1000, 'USD'),
            'status' => 'attributed',
            'attributed_program_id' => $programB->id,
        ]);
        $entryB = CommissionLedgerEntry::create([
            'account_id' => $accountB->id,
            'conversion_id' => $conversionB->id,
            'program_id' => $programB->id,
            'amount' => Money::of(100, 'USD'),
            'type' => 'commission',
            'created_at' => now(),
        ]);

        app(ClosePayoutBatch::class)->handle($batchA);

        $this->assertFalse(DB::table('payout_batch_entries')
            ->where('payout_batch_id', $batchA->id)
            ->where('commission_ledger_entry_id', $entryB->id)
            ->exists());
    }

    #[Test]
    public function itRefusesToCloseAnAlreadyClosedBatch(): void
    {
        $batch = app(OpenPayoutBatch::class)->handle(Account::factory()->create());
        app(ClosePayoutBatch::class)->handle($batch);

        $this->expectException(LogicException::class);

        app(ClosePayoutBatch::class)->handle($batch->fresh());
    }
}
