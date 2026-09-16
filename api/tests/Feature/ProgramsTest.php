<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Program;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class ProgramsTest extends TestCase
{
    use RefreshDatabase;

    private const JSON_API_MEDIA_TYPE = 'application/vnd.api+json';

    #[Test]
    public function itCreatesAProgram(): void
    {
        $response = $this->postJsonApi('/api/v1/programs', [
            'name' => 'Acme Affiliates',
            'slug' => 'acme-affiliates',
        ]);

        $response->assertCreated();
        $response->assertHeader('Content-Type', self::JSON_API_MEDIA_TYPE);
        $response->assertJsonPath('data.type', 'programs');
        $response->assertJsonPath('data.attributes.name', 'Acme Affiliates');
        $response->assertJsonPath('data.attributes.slug', 'acme-affiliates');
        $response->assertJsonPath('data.attributes.status', 'draft');
        $response->assertJsonPath('data.links.self', fn (string $self): bool => str_ends_with($self, '/api/v1/programs/'.$response->json('data.id')));

        $this->assertDatabaseHas('programs', [
            'slug' => 'acme-affiliates',
            'name' => 'Acme Affiliates',
        ]);
    }

    #[Test]
    public function itCreatesAProgramWithACommissionStrategy(): void
    {
        $response = $this->postJsonApi('/api/v1/programs', [
            'name' => 'Acme Affiliates',
            'slug' => 'acme-percentage',
            'commissionStrategy' => 'percentage',
            'commissionRate' => '0.1000',
        ]);

        $response->assertCreated();
        $response->assertJsonPath('data.attributes.commissionStrategy', 'percentage');
        $response->assertJsonPath('data.attributes.commissionRate', '0.1000');

        $this->assertDatabaseHas('programs', [
            'slug' => 'acme-percentage',
            'commission_strategy' => 'percentage',
        ]);
    }

    #[Test]
    public function itRequiresACommissionRateWhenTheStrategyIsPercentage(): void
    {
        $response = $this->postJsonApi('/api/v1/programs', [
            'name' => 'Acme Affiliates',
            'slug' => 'acme-missing-rate',
            'commissionStrategy' => 'percentage',
        ]);

        $response->assertUnprocessable();
        $response->assertJsonPath('errors.0.source.pointer', '/data/attributes/commissionRate');
    }

    #[Test]
    public function itRejectsADuplicateSlugWithAJsonApiErrorDocument(): void
    {
        Program::create(['name' => 'Acme', 'slug' => 'acme', 'status' => 'draft']);

        $response = $this->postJsonApi('/api/v1/programs', [
            'name' => 'Acme Again',
            'slug' => 'acme',
        ]);

        $response->assertUnprocessable();
        $response->assertHeader('Content-Type', self::JSON_API_MEDIA_TYPE);
        $response->assertJsonPath('errors.0.status', '422');
        $response->assertJsonPath('errors.0.source.pointer', '/data/attributes/slug');
        $response->assertJsonMissing(['message']);
    }

    #[Test]
    public function itListsPrograms(): void
    {
        Program::factory()->count(3)->create();

        $response = $this->getJson('/api/v1/programs', [
            'Accept' => self::JSON_API_MEDIA_TYPE,
        ]);

        $response->assertOk();
        $response->assertHeader('Content-Type', self::JSON_API_MEDIA_TYPE);
        $response->assertJsonCount(3, 'data');
        $response->assertJsonStructure([
            'data' => [
                '*' => ['type', 'id', 'attributes' => ['name', 'slug', 'status'], 'links' => ['self']],
            ],
            'links' => ['first', 'last'],
            'meta' => ['page'],
        ]);
    }

    private function postJsonApi(string $uri, array $attributes): TestResponse
    {
        return $this->postJson($uri, [
            'data' => [
                'type' => 'programs',
                'attributes' => $attributes,
            ],
        ], [
            'CONTENT_TYPE' => self::JSON_API_MEDIA_TYPE,
            'Accept' => self::JSON_API_MEDIA_TYPE,
        ]);
    }
}
