<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Support\Clock;
use App\Support\SystemClock;
use DateTimeImmutable;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class ClockTest extends TestCase
{
    #[Test]
    public function itResolvesTheRealClockFromTheContainerByDefault(): void
    {
        $this->assertInstanceOf(SystemClock::class, app(Clock::class));
    }

    #[Test]
    public function itReportsTheCurrentTime(): void
    {
        $before = new DateTimeImmutable;
        $now = app(Clock::class)->now();
        $after = new DateTimeImmutable;

        $this->assertGreaterThanOrEqual($before, $now);
        $this->assertLessThanOrEqual($after, $now);
    }

    #[Test]
    public function itCanBeFakedInTestsWithoutTouchingCarbonSetTestNow(): void
    {
        $frozen = new DateTimeImmutable('2030-01-01T00:00:00+00:00');

        $fake = new class($frozen) implements Clock
        {
            public function __construct(private DateTimeImmutable $time) {}

            public function now(): DateTimeImmutable
            {
                return $this->time;
            }
        };

        app()->instance(Clock::class, $fake);

        $this->assertEquals($frozen, app(Clock::class)->now());
    }
}
