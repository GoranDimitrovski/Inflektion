<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Account;
use App\Models\Link;
use App\Models\Program;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class LinksTest extends TestCase
{
    use RefreshDatabase;

    private const JSON_API_MEDIA_TYPE = 'application/vnd.api+json';

    #[Test]
    public function itCreatesALink(): void
    {
        $account = $this->actingAsAccountMember();
        $program = Program::factory()->for($account)->create();

        $response = $this->postJsonApi($account, [
            'programId' => $program->id,
            'destinationUrl' => 'https://example.test/landing',
        ]);

        $response->assertCreated();
        $response->assertHeader('Content-Type', self::JSON_API_MEDIA_TYPE);
        $response->assertJsonPath('data.type', 'links');
        $response->assertJsonPath('data.attributes.programId', $program->id);
        $response->assertJsonPath('data.attributes.destinationUrl', 'https://example.test/landing');
        $response->assertJsonPath('data.attributes.status', 'active');
        $response->assertJsonPath(
            'data.attributes.redirectUrl',
            fn (string $url): bool => str_contains($url, '/r/'),
        );

        $this->assertDatabaseHas('links', [
            'account_id' => $account->id,
            'program_id' => $program->id,
            'destination_url' => 'https://example.test/landing',
        ]);
    }

    #[Test]
    public function itRejectsAProgramFromAnotherAccount(): void
    {
        $account = $this->actingAsAccountMember();
        $otherProgram = Program::factory()->create();

        $response = $this->postJsonApi($account, [
            'programId' => $otherProgram->id,
            'destinationUrl' => 'https://example.test/landing',
        ]);

        $response->assertUnprocessable();
        $response->assertJsonPath('errors.0.source.pointer', '/data/attributes/programId');
    }

    #[Test]
    public function itUpdatesAStatus(): void
    {
        $account = $this->actingAsAccountMember();
        $link = Link::factory()->for(Program::factory()->for($account))->create(['status' => 'active']);

        $response = $this->patchJson("/api/v1/accounts/{$account->id}/links/{$link->id}", [
            'data' => [
                'type' => 'links',
                'id' => (string) $link->id,
                'attributes' => ['status' => 'paused'],
            ],
        ], [
            'CONTENT_TYPE' => self::JSON_API_MEDIA_TYPE,
            'Accept' => self::JSON_API_MEDIA_TYPE,
        ]);

        $response->assertOk();
        $response->assertJsonPath('data.attributes.status', 'paused');
        $this->assertSame('paused', $link->fresh()->status);
    }

    #[Test]
    public function itListsLinksFilteredByProgram(): void
    {
        $account = $this->actingAsAccountMember();
        $programA = Program::factory()->for($account)->create();
        $programB = Program::factory()->for($account)->create();

        Link::factory()->for($programA)->count(2)->create();
        Link::factory()->for($programB)->create();

        $response = $this->getJson(
            "/api/v1/accounts/{$account->id}/links?filter[programId]={$programA->id}",
            ['Accept' => self::JSON_API_MEDIA_TYPE],
        );

        $response->assertOk();
        $response->assertJsonCount(2, 'data');
    }

    #[Test]
    public function itNeverReturnsAnotherAccountsLinks(): void
    {
        $otherAccount = Account::factory()->create();
        Link::factory()->for(Program::factory()->for($otherAccount))->count(2)->create();

        $account = $this->actingAsAccountMember();
        Link::factory()->for(Program::factory()->for($account))->create();

        $response = $this->getJson("/api/v1/accounts/{$account->id}/links", [
            'Accept' => self::JSON_API_MEDIA_TYPE,
        ]);

        $response->assertOk();
        $response->assertJsonCount(1, 'data');
    }

    #[Test]
    public function itReturns401ForAnUnauthenticatedRequest(): void
    {
        $account = Account::factory()->create();

        $response = $this->getJson("/api/v1/accounts/{$account->id}/links", [
            'Accept' => self::JSON_API_MEDIA_TYPE,
        ]);

        $response->assertUnauthorized();
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function postJsonApi(Account $account, array $attributes): TestResponse
    {
        return $this->postJson("/api/v1/accounts/{$account->id}/links", [
            'data' => [
                'type' => 'links',
                'attributes' => $attributes,
            ],
        ], [
            'CONTENT_TYPE' => self::JSON_API_MEDIA_TYPE,
            'Accept' => self::JSON_API_MEDIA_TYPE,
        ]);
    }
}
