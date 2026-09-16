<?php

declare(strict_types=1);

namespace App\Integrations\Storefronts\DemoStore;

use App\Integrations\Storefronts\Exceptions\InvalidPostbackPayloadException;
use App\Integrations\Storefronts\PostbackData;
use App\Integrations\Storefronts\StorefrontAdapter;
use App\Support\Money;

final class DemoStoreAdapter implements StorefrontAdapter
{
    public function parsePostback(array $payload): PostbackData
    {
        $this->assertRequiredFields($payload);
        $this->assertValidSignature($payload);

        if ($payload['event'] !== 'purchase') {
            throw new InvalidPostbackPayloadException("Unsupported DemoStore postback event [{$payload['event']}].");
        }

        return new PostbackData(
            externalId: (string) $payload['order_id'],
            clickId: $payload['sub_id'] !== '' ? (string) $payload['sub_id'] : null,
            amount: Money::of((int) round(((float) $payload['amount']) * 100), (string) $payload['currency']),
            eventType: (string) $payload['event'],
        );
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function assertRequiredFields(array $payload): void
    {
        foreach (['order_id', 'sub_id', 'amount', 'currency', 'event', 'signature'] as $field) {
            if (! array_key_exists($field, $payload)) {
                throw new InvalidPostbackPayloadException("DemoStore postback is missing required field [{$field}].");
            }
        }
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function assertValidSignature(array $payload): void
    {
        $expected = self::sign($payload, (string) config('services.demo_store.secret'));

        if (! hash_equals($expected, (string) $payload['signature'])) {
            throw new InvalidPostbackPayloadException('DemoStore postback signature is invalid.');
        }
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public static function sign(array $payload, string $secret): string
    {
        $message = implode('|', [
            $payload['order_id'] ?? '',
            $payload['sub_id'] ?? '',
            $payload['amount'] ?? '',
            $payload['currency'] ?? '',
            $payload['event'] ?? '',
        ]);

        return hash_hmac('sha256', $message, $secret);
    }
}
