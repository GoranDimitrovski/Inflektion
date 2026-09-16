<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Support\Outbox\OutboxPublisher;
use Illuminate\Console\Command;

final class PublishOutboxMessages extends Command
{
    protected $signature = 'outbox:publish';

    protected $description = 'Publish pending outbox messages as domain events';

    public function handle(OutboxPublisher $publisher): int
    {
        $count = $publisher->publishPending();

        $this->info("Published {$count} outbox message(s).");

        return self::SUCCESS;
    }
}
