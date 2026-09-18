<?php

declare(strict_types=1);

namespace App\Support\Outbox;

use App\Events\OutboxMessagePublished;

final class OutboxPublisher
{
    public function publishPending(): int
    {
        $published = 0;

        OutboxMessage::query()
            ->whereNull('published_at')
            ->orderBy('id')
            ->each(function (OutboxMessage $message) use (&$published): void {
                event(new OutboxMessagePublished($message));

                $message->update(['published_at' => now()]);

                $published++;
            });

        return $published;
    }
}
