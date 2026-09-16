<?php

declare(strict_types=1);

namespace App\Support\Outbox;

use App\Events\OutboxMessagePublished;
use App\Support\Clock;

final class OutboxPublisher
{
    public function __construct(
        private readonly Clock $clock,
    ) {}

    public function publishPending(): int
    {
        $published = 0;

        OutboxMessage::query()
            ->whereNull('published_at')
            ->orderBy('id')
            ->each(function (OutboxMessage $message) use (&$published): void {
                event(new OutboxMessagePublished($message));

                $message->update(['published_at' => $this->clock->now()]);

                $published++;
            });

        return $published;
    }
}
