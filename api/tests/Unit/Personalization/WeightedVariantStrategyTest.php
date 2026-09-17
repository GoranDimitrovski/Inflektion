<?php

declare(strict_types=1);

namespace Tests\Unit\Personalization;

use App\Models\Link;
use App\Personalization\PersonalizationContext;
use App\Personalization\Strategies\WeightedVariantStrategy;
use DateTimeImmutable;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class WeightedVariantStrategyTest extends TestCase
{
    #[Test]
    public function itAlwaysDecidesAVariantFromTheFixedSet(): void
    {
        $strategy = new WeightedVariantStrategy;
        $link = new Link;
        $context = new PersonalizationContext(new DateTimeImmutable, null);

        $seen = [];

        for ($i = 0; $i < 50; $i++) {
            $variant = $strategy->decide($link, $context);

            $this->assertContains($variant->key, ['control', 'variant-a', 'variant-b']);
            $this->assertSame(['variant' => $variant->key], $variant->queryParams);

            $seen[$variant->key] = true;
        }

        $this->assertGreaterThan(1, count($seen));
    }
}
