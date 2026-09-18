<?php

declare(strict_types=1);

namespace Tests\Contract\Storefronts;

use App\Integrations\Storefronts\DemoStore\DemoStoreAdapter;
use App\Integrations\Storefronts\Exceptions\InvalidPostbackPayloadException;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class DemoStoreAdapterTest extends TestCase
{
    #[Test]
    public function itParsesARecordedDemoStorePurchasePostbackIntoNormalizedPostbackData(): void
    {
        $data = (new DemoStoreAdapter)->parsePostback($this->demoStoreFixture());

        $this->assertSame('DS-100293', $data->externalId);
        $this->assertSame('8f3c1e2a9b', $data->clickId);
        $this->assertSame(4999, $data->amount->minorUnits);
        $this->assertSame('USD', $data->amount->currency);
    }

    #[Test]
    public function itRejectsAPostbackMissingARequiredField(): void
    {
        $payload = $this->demoStoreFixture();
        unset($payload['amount']);

        $this->expectException(InvalidPostbackPayloadException::class);

        (new DemoStoreAdapter)->parsePostback($payload);
    }

    #[Test]
    public function itRejectsAPostbackReportingAnUnsupportedEventType(): void
    {
        $payload = $this->demoStoreFixture();
        $payload['event'] = 'refund';

        $this->expectException(InvalidPostbackPayloadException::class);

        (new DemoStoreAdapter)->parsePostback($payload);
    }

    #[Test]
    public function itRejectsAnInvalidSignature(): void
    {
        $payload = $this->demoStoreFixture();
        $payload['signature'] = 'not-a-real-signature';

        $this->expectException(InvalidPostbackPayloadException::class);

        (new DemoStoreAdapter)->parsePostback($payload);
    }

    /**
     * @return array<string, mixed>
     */
    private function demoStoreFixture(): array
    {
        return json_decode(
            file_get_contents(__DIR__.'/../../Fixtures/storefronts/demo_store_postback.json'),
            associative: true,
            flags: JSON_THROW_ON_ERROR,
        );
    }
}
