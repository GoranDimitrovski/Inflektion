<?php

declare(strict_types=1);

namespace App\Integrations\Storefronts\DemoStore;

use App\Integrations\Storefronts\Exceptions\InvalidPostbackPayloadException;
use App\Integrations\Storefronts\PostbackData;
use App\Integrations\Storefronts\StorefrontAdapter;
use App\Support\Money;

final class DemoStoreFakeAdapter implements StorefrontAdapter
{
    /**
     * @param  array<string, mixed>  $payload
     */
    public function parsePostback(array $payload): PostbackData
    {
        foreach (['order_id', 'sub_id', 'amount', 'currency', 'event'] as $field) {
            if (! array_key_exists($field, $payload)) {
                throw new InvalidPostbackPayloadException("DemoStore postback is missing required field [{$field}].");
            }
        }

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
}
