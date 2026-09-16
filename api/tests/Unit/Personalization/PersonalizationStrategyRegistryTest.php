<?php

declare(strict_types=1);

namespace Tests\Unit\Personalization;

use App\Models\Link;
use App\Personalization\PersonalizationContext;
use App\Personalization\PersonalizationStrategy;
use App\Personalization\PersonalizationStrategyRegistry;
use App\Personalization\Strategies\NullVariantStrategy;
use App\Personalization\Variant;
use DateTimeImmutable;
use Illuminate\Support\Facades\Log;
use PHPUnit\Framework\Attributes\Test;
use RuntimeException;
use Tests\TestCase;

final class ThrowingVariantStrategy implements PersonalizationStrategy
{
    public function decide(Link $link, PersonalizationContext $context): Variant
    {
        throw new RuntimeException('personalization rule is broken');
    }
}

final class PersonalizationStrategyRegistryTest extends TestCase
{
    #[Test]
    public function itFallsBackToTheNullVariantWhenNoStrategyIdentifierIsSet(): void
    {
        $registry = new PersonalizationStrategyRegistry(new NullVariantStrategy, []);

        $variant = $registry->decide(null, new Link, $this->personalizationContext());

        $this->assertEquals(Variant::none(), $variant);
    }

    #[Test]
    public function itFallsBackToTheNullVariantForAnUnknownStrategyIdentifier(): void
    {
        $registry = new PersonalizationStrategyRegistry(new NullVariantStrategy, []);

        $variant = $registry->decide('does-not-exist', new Link, $this->personalizationContext());

        $this->assertEquals(Variant::none(), $variant);
    }

    #[Test]
    public function itFallsBackToTheNullVariantAndLogsWhenAStrategyThrows(): void
    {
        Log::spy();

        $registry = new PersonalizationStrategyRegistry(new NullVariantStrategy, [
            'throwing' => ThrowingVariantStrategy::class,
        ]);

        $variant = $registry->decide('throwing', new Link, $this->personalizationContext());

        $this->assertEquals(Variant::none(), $variant);

        Log::shouldHaveReceived('warning')->once()->withArgs(
            fn (string $message, array $context): bool => $context['strategy'] === 'throwing'
                && $context['exception'] === RuntimeException::class,
        );
    }

    private function personalizationContext(): PersonalizationContext
    {
        return new PersonalizationContext(new DateTimeImmutable, 'Mozilla/5.0');
    }
}
