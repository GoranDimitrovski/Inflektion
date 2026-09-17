<?php

declare(strict_types=1);

namespace App\Integrations\Storefronts;

interface StorefrontAdapter
{
    /**
     * @param  array<string, mixed>  $payload
     */
    public function parsePostback(array $payload): PostbackData;
}
