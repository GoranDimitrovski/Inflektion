<?php

declare(strict_types=1);

namespace Tests\Unit\Personalization;

use App\Models\Link;
use App\Personalization\PersonalizationContext;
use App\Personalization\Strategies\NullVariantStrategy;
use App\Personalization\Variant;
use DateTimeImmutable;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class NullVariantStrategyTest extends TestCase
{
    #[Test]
    public function itAlwaysDecidesTheDefaultVariantWithNoQueryParams(): void
    {
        $strategy = new NullVariantStrategy;

        $variant = $strategy->decide(new Link, new PersonalizationContext(new DateTimeImmutable, null));

        $this->assertEquals(Variant::none(), $variant);
        $this->assertSame('default', $variant->key);
        $this->assertSame([], $variant->queryParams);
    }
}
