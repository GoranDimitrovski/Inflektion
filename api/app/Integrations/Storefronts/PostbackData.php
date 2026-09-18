<?php

declare(strict_types=1);

namespace App\Integrations\Storefronts;

use App\Support\Money;

final class PostbackData
{
    public function __construct(
        public readonly string $externalId,
        public readonly ?string $clickId,
        public readonly Money $amount,
    ) {}
}
