<?php

declare(strict_types=1);

namespace Tests\Feature\Tracking;

use App\Models\Click;
use App\Models\Link;
use App\Personalization\PersonalizationContext;
use App\Personalization\PersonalizationStrategy;
use App\Personalization\PersonalizationStrategyRegistry;
use App\Personalization\Strategies\NullVariantStrategy;
use App\Personalization\Variant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use RuntimeException;
use Tests\TestCase;

final class RedirectTestThrowingStrategy implements PersonalizationStrategy
{
    public function decide(Link $link, PersonalizationContext $context): Variant
    {
        throw new RuntimeException('personalization rule is broken');
    }
}

final class RedirectTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function itRecordsAClickAndRedirectsToTheDestinationUrlWithAClickId(): void
    {
        $link = Link::factory()->create([
            'destination_url' => 'https://example.com/shop',
            'token' => 'abc12345',
            'status' => 'active',
        ]);

        $response = $this->get('/r/abc12345');

        $response->assertRedirect();
        $response->assertStatus(302);

        $location = $response->headers->get('Location');
        $this->assertStringStartsWith('https://example.com/shop?click_id=', $location);

        $this->assertDatabaseHas('clicks', [
            'link_id' => $link->id,
        ]);

        $click = Click::query()->where('link_id', $link->id)->firstOrFail();
        $this->assertSame('https://example.com/shop?click_id='.$click->id, $location);
    }

    #[Test]
    public function itReturns404ForAnUnknownToken(): void
    {
        $this->get('/r/does-not-exist')->assertNotFound();
    }

    #[Test]
    public function itReturns404ForAnInactiveLink(): void
    {
        Link::factory()->create(['token' => 'inactive1', 'status' => 'paused']);

        $this->get('/r/inactive1')->assertNotFound();
    }

    #[Test]
    public function itStillRedirectsWhenTheLinkHasNoPersonalizationStrategy(): void
    {
        Link::factory()->create([
            'destination_url' => 'https://example.com/shop',
            'token' => 'nostrat01',
            'personalization_strategy' => null,
        ]);

        $response = $this->get('/r/nostrat01');

        $response->assertStatus(302);
        $this->assertStringStartsWith('https://example.com/shop?click_id=', $response->headers->get('Location'));
    }

    #[Test]
    public function itStillRedirectsWhenTheLinkHasAnUnknownPersonalizationStrategy(): void
    {
        Link::factory()->create([
            'destination_url' => 'https://example.com/shop',
            'token' => 'unknownst',
            'personalization_strategy' => 'does-not-exist',
        ]);

        $response = $this->get('/r/unknownst');

        $response->assertStatus(302);
        $this->assertStringStartsWith('https://example.com/shop?click_id=', $response->headers->get('Location'));
    }

    #[Test]
    public function itStillRedirectsAndAppendsTheDecidedVariantForAConfiguredStrategy(): void
    {
        Link::factory()->create([
            'destination_url' => 'https://example.com/shop',
            'token' => 'weighted1',
            'personalization_strategy' => 'weighted',
        ]);

        $response = $this->get('/r/weighted1');

        $response->assertStatus(302);
        $location = $response->headers->get('Location');
        $this->assertStringStartsWith('https://example.com/shop?click_id=', $location);
        $this->assertStringContainsString('&variant=', $location);
    }

    #[Test]
    public function itStillRedirectsWhenTheConfiguredStrategyThrowsWhileDeciding(): void
    {
        app()->instance(
            PersonalizationStrategyRegistry::class,
            new PersonalizationStrategyRegistry(new NullVariantStrategy, [
                'broken' => RedirectTestThrowingStrategy::class,
            ]),
        );

        Link::factory()->create([
            'destination_url' => 'https://example.com/shop',
            'token' => 'brokenst1',
            'personalization_strategy' => 'broken',
        ]);

        $response = $this->get('/r/brokenst1');

        $response->assertStatus(302);
        $this->assertStringStartsWith('https://example.com/shop?click_id=', $response->headers->get('Location'));
    }
}
