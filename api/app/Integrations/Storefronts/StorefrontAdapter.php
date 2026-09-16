<?php

declare(strict_types=1);

namespace App\Integrations\Storefronts;

use App\Integrations\Storefronts\Exceptions\InvalidPostbackPayloadException;

interface StorefrontAdapter
{
    /**
     * @param  array<string, mixed>  $payload
     *
     * @throws InvalidPostbackPayloadException
     */
    public function parsePostback(array $payload): PostbackData;
}
