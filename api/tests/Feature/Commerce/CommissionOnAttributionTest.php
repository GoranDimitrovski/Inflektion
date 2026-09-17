<?php

declare(strict_types=1);

namespace Tests\Feature\Commerce;

use App\Integrations\Storefronts\DemoStore\DemoStoreAdapter;
use App\Models\Click;
use App\Models\CommissionLedgerEntry;
use App\Models\Conversion;
use App\Models\Link;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class CommissionOnAttributionTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function itWritesACommissionLedgerEntryOnceAPostbackConversionGetsAttributedToAProgram(): void
    {
        $link = Link::factory()->create(['status' => 'active']);
        $link->program()->update([
            'commission_strategy' => 'percentage',
            'commission_rate' => '0.1000',
        ]);

        $click = Click::create([
            'account_id' => $link->account_id,
            'link_id' => $link->id,
            'ip_address' => '127.0.0.1',
            'user_agent' => 'phpunit',
            'occurred_at' => now(),
        ]);

        $payload = json_decode(
            file_get_contents(__DIR__.'/../../Fixtures/storefronts/demo_store_postback.json'),
            associative: true,
            flags: JSON_THROW_ON_ERROR,
        );
        $payload['sub_id'] = (string) $click->id;
        $payload['signature'] = DemoStoreAdapter::sign($payload, (string) config('services.demo_store.secret'));

        $this->postJson('/api/v1/postbacks/demo-store', $payload)->assertStatus(202);

        $conversion = Conversion::query()->where('external_id', 'DS-100293')->firstOrFail();
        $this->assertSame('attributed', $conversion->status);

        $entry = CommissionLedgerEntry::query()->where('conversion_id', $conversion->id)->first();
        $this->assertNotNull($entry);
        $this->assertSame((int) round($conversion->amount->minorUnits * 0.1), $entry->amount->minorUnits);

        $this->assertDatabaseHas('outbox_messages', [
            'aggregate_type' => 'commission_ledger_entry',
            'aggregate_id' => (string) $entry->id,
            'event_type' => 'CommissionWritten',
        ]);
    }
}
