<?php

declare(strict_types=1);

namespace App\Events;

use App\Support\Outbox\OutboxMessage;

final class OutboxMessagePublished
{
    public function __construct(
        public readonly OutboxMessage $message,
    ) {}
}
