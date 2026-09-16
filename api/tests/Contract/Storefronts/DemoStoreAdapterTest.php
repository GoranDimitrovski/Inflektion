<?php

declare(strict_types=1);

namespace Tests\Contract\Storefronts;

use App\Integrations\Storefronts\DemoStore\DemoStoreAdapter;
use App\Integrations\Storefronts\DemoStore\DemoStoreFakeAdapter;
use App\Integrations\Storefronts\Exceptions\InvalidPostbackPayloadException;
use App\Integrations\Storefronts\StorefrontAdapter;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class DemoStoreAdapterTest extends TestCase
{
    #[Test]
    #[DataProvider('demoStoreAdapters')]
    public function itParsesARecordedDemoStorePurchasePostbackIntoNormalizedPostbackData(callable $makeAdapter): void
    {
        $adapter = $makeAdapter();

        $data = $adapter->parsePostback($this->demoStoreFixture());

        $this->assertSame('DS-100293', $data->externalId);
        $this->assertSame('8f3c1e2a9b', $data->clickId);
        $this->assertSame(4999, $data->amount->minorUnits);
        $this->assertSame('USD', $data->amount->currency);
        $this->assertSame('purchase', $data->eventType);
    }

    #[Test]
    #[DataProvider('demoStoreAdapters')]
    public function itRejectsAPostbackMissingARequiredField(callable $makeAdapter): void
    {
        $adapter = $makeAdapter();
        $payload = $this->demoStoreFixture();
        unset($payload['amount']);

        $this->expectException(InvalidPostbackPayloadException::class);

        $adapter->parsePostback($payload);
    }

    #[Test]
    #[DataProvider('demoStoreAdapters')]
    public function itRejectsAPostbackReportingAnUnsupportedEventType(callable $makeAdapter): void
    {
        $adapter = $makeAdapter();
        $payload = $this->demoStoreFixture();
        $payload['event'] = 'refund';

        $this->expectException(InvalidPostbackPayloadException::class);

        $adapter->parsePostback($payload);
    }

    #[Test]
    public function itRejectsAnInvalidSignatureRealAdapterOnly(): void
    {
        $payload = $this->demoStoreFixture();
        $payload['signature'] = 'not-a-real-signature';

        $this->expectException(InvalidPostbackPayloadException::class);

        (new DemoStoreAdapter)->parsePostback($payload);
    }

    /**
     * @return array<string, array{0: callable(): StorefrontAdapter}>
     */
    public static function demoStoreAdapters(): array
    {
        return [
            'real adapter' => [fn (): StorefrontAdapter => new DemoStoreAdapter],
            'fake adapter' => [fn (): StorefrontAdapter => new DemoStoreFakeAdapter],
        ];
    }

    private function demoStoreFixture(): array
    {
        return json_decode(
            file_get_contents(__DIR__.'/../../Fixtures/storefronts/demo_store_postback.json'),
            associative: true,
            flags: JSON_THROW_ON_ERROR,
        );
    }
}
