<?php

declare(strict_types=1);

namespace Tests\Unit\Support\Outbox;

use App\Events\OutboxMessagePublished;
use App\Support\Clock;
use App\Support\Outbox\OutboxMessage;
use App\Support\Outbox\OutboxPublisher;
use DateTimeImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class OutboxPublisherTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function itPublishesPendingOutboxMessagesAndStampsThemAsPublished(): void
    {
        Event::fake();

        $frozen = new DateTimeImmutable('2030-01-01T00:00:00+00:00');
        $clock = new class($frozen) implements Clock
        {
            public function __construct(private readonly DateTimeImmutable $time) {}

            public function now(): DateTimeImmutable
            {
                return $this->time;
            }
        };

        $message = OutboxMessage::create([
            'aggregate_type' => 'conversion',
            'aggregate_id' => '1',
            'event_type' => 'conversion.recorded',
            'payload' => ['foo' => 'bar'],
            'created_at' => $clock->now(),
        ]);

        $publisher = new OutboxPublisher($clock);
        $published = $publisher->publishPending();

        $this->assertSame(1, $published);

        Event::assertDispatched(
            OutboxMessagePublished::class,
            fn (OutboxMessagePublished $event): bool => $event->message->is($message),
        );

        $message->refresh();
        $this->assertNotNull($message->published_at);
        $this->assertEquals($clock->now(), $message->published_at);
    }

    #[Test]
    public function itDoesNotRepublishAnAlreadyPublishedMessage(): void
    {
        Event::fake();

        OutboxMessage::create([
            'aggregate_type' => 'conversion',
            'aggregate_id' => '1',
            'event_type' => 'conversion.recorded',
            'payload' => [],
            'published_at' => now(),
            'created_at' => now(),
        ]);

        $published = app(OutboxPublisher::class)->publishPending();

        $this->assertSame(0, $published);
        Event::assertNotDispatched(OutboxMessagePublished::class);
    }
}
