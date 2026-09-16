<?php

declare(strict_types=1);

namespace App\Personalization;

use DateTimeInterface;

final class PersonalizationContext
{
    public function __construct(
        public readonly DateTimeInterface $occurredAt,
        public readonly ?string $userAgent,
    ) {}
}
