<?php

declare(strict_types=1);

namespace Tests\Unit\Models;

use App\Models\CommissionLedgerEntry;
use App\Models\Conversion;
use App\Models\Program;
use App\Support\Money;
use Illuminate\Foundation\Testing\RefreshDatabase;
use LogicException;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class CommissionLedgerEntryTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function itRefusesToUpdateALedgerEntry(): void
    {
        $entry = $this->makeCommissionLedgerEntry();

        $this->expectException(LogicException::class);

        $entry->update(['type' => 'adjustment']);
    }

    #[Test]
    public function itRefusesToDeleteALedgerEntry(): void
    {
        $entry = $this->makeCommissionLedgerEntry();

        $this->expectException(LogicException::class);

        try {
            $entry->delete();
        } finally {
            $this->assertTrue(CommissionLedgerEntry::query()->whereKey($entry->id)->exists());
        }
    }

    private function makeCommissionLedgerEntry(): CommissionLedgerEntry
    {
        $program = Program::factory()->create();
        $conversion = Conversion::create([
            'account_id' => $program->account_id,
            'vendor' => 'demo-store',
            'external_id' => 'ledger-append-only-'.uniqid(),
            'amount' => Money::of(1000, 'USD'),
            'status' => 'attributed',
            'attributed_program_id' => $program->id,
        ]);

        return CommissionLedgerEntry::create([
            'account_id' => $program->account_id,
            'conversion_id' => $conversion->id,
            'program_id' => $program->id,
            'amount' => Money::of(100, 'USD'),
            'type' => 'commission',
            'created_at' => now(),
        ]);
    }
}
