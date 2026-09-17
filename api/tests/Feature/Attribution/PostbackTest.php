<?php

declare(strict_types=1);

namespace Tests\Feature\Attribution;

use App\Integrations\Storefronts\DemoStore\DemoStoreAdapter;
use App\Models\Click;
use App\Models\Conversion;
use App\Models\Link;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PostbackTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function itAcceptsADemoStorePostbackAndRecordsAConversion(): void
    {
        $response = $this->postJson('/api/v1/postbacks/demo-store', $this->demoStorePostbackPayload());

        $response->assertStatus(202);
        $response->assertJsonPath('data.external_id', 'DS-100293');
        $response->assertJsonPath('data.status', 'recorded');

        $this->assertDatabaseCount('conversions', 1);
        $this->assertDatabaseHas('conversions', [
            'vendor' => 'demo-store',
            'external_id' => 'DS-100293',
            'amount_minor_units' => 4999,
            'amount_currency' => 'USD',
        ]);
    }

    #[Test]
    public function itIsIdempotentUnderADuplicatePostbackDelivery(): void
    {
        $payload = $this->demoStorePostbackPayload();

        $first = $this->postJson('/api/v1/postbacks/demo-store', $payload);
        $second = $this->postJson('/api/v1/postbacks/demo-store', $payload);

        $first->assertStatus(202);
        $second->assertStatus(202);
        $second->assertJsonPath('data.external_id', 'DS-100293');

        $this->assertDatabaseCount('conversions', 1);
    }

    #[Test]
    public function itRejectsAnUnknownVendor(): void
    {
        $this->postJson('/api/v1/postbacks/unknown-vendor', $this->demoStorePostbackPayload())
            ->assertStatus(422);

        $this->assertDatabaseCount('conversions', 0);
    }

    #[Test]
    public function itRejectsAnEmptyPayload(): void
    {
        $this->postJson('/api/v1/postbacks/demo-store', [])
            ->assertStatus(422)
            ->assertJsonValidationErrors('body');

        $this->assertDatabaseCount('conversions', 0);
    }

    #[Test]
    public function itReturns404ForAMalformedVendorSegment(): void
    {
        $this->postJson('/api/v1/postbacks/Demo_Store!', $this->demoStorePostbackPayload())
            ->assertStatus(404);
    }

    #[Test]
    public function itAttributesAConversionBackToTheLinkAndProgramItCameFrom(): void
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

        $payload = $this->demoStorePostbackPayload();
        $payload['sub_id'] = (string) $click->id;
        $payload['signature'] = DemoStoreAdapter::sign(
            $payload,
            (string) config('services.demo_store.secret'),
        );

        $this->postJson('/api/v1/postbacks/demo-store', $payload)->assertStatus(202);

        $conversion = Conversion::query()->where('external_id', 'DS-100293')->firstOrFail();

        $this->assertSame($click->id, $conversion->click_id);
        $this->assertSame($link->program_id, $conversion->attributed_program_id);
        $this->assertSame('attributed', $conversion->status);
    }

    private function demoStorePostbackPayload(): array
    {
        return json_decode(
            file_get_contents(__DIR__.'/../../Fixtures/storefronts/demo_store_postback.json'),
            associative: true,
            flags: JSON_THROW_ON_ERROR,
        );
    }
}
