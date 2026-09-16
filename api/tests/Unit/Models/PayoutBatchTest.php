<?php

declare(strict_types=1);

namespace Tests\Unit\Models;

use App\Models\PayoutBatch;
use DateTimeImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use LogicException;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class PayoutBatchTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function itClosesAnOpenBatchRecordingClosedAtAndFlippingItsStatus(): void
    {
        $batch = PayoutBatch::create(['status' => PayoutBatch::STATUS_OPEN, 'opened_at' => now()]);

        $closedAt = new DateTimeImmutable('2030-01-01T00:00:00+00:00');
        $batch->close($closedAt);

        $this->assertSame(PayoutBatch::STATUS_CLOSED, $batch->status);
        $this->assertEquals($closedAt, $batch->closed_at);
        $this->assertFalse($batch->isOpen());
    }

    #[Test]
    public function itRefusesToCloseAnAlreadyClosedBatch(): void
    {
        $batch = PayoutBatch::create([
            'status' => PayoutBatch::STATUS_CLOSED,
            'opened_at' => now(),
            'closed_at' => now(),
        ]);

        $this->expectException(LogicException::class);

        $batch->close(new DateTimeImmutable);
    }
}
