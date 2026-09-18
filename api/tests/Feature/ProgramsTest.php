<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Account;
use App\Models\Program;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class ProgramsTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function itCreatesAProgram(): void
    {
        $account = $this->actingAsAccountMember();

        $response = $this->postJsonApi($account, 'programs', [
            'name' => 'Acme Affiliates',
            'slug' => 'acme-affiliates',
        ]);

        $response->assertCreated();
        $response->assertHeader('Content-Type', self::JSON_API_MEDIA_TYPE);
        $response->assertJsonPath('data.type', 'programs');
        $response->assertJsonPath('data.attributes.name', 'Acme Affiliates');
        $response->assertJsonPath('data.attributes.slug', 'acme-affiliates');
        $response->assertJsonPath('data.attributes.status', 'draft');
        $response->assertJsonPath(
            'data.links.self',
            fn (string $self): bool => str_ends_with($self, "/api/v1/accounts/{$account->id}/programs/".$response->json('data.id')),
        );

        $this->assertDatabaseHas('programs', [
            'account_id' => $account->id,
            'slug' => 'acme-affiliates',
            'name' => 'Acme Affiliates',
        ]);
    }

    #[Test]
    public function itCreatesAProgramWithACommissionStrategy(): void
    {
        $account = $this->actingAsAccountMember();

        $response = $this->postJsonApi($account, 'programs', [
            'name' => 'Acme Affiliates',
            'slug' => 'acme-percentage',
            'commissionStrategy' => 'percentage',
            'commissionRate' => '0.1000',
        ]);

        $response->assertCreated();
        $response->assertJsonPath('data.attributes.commissionStrategy', 'percentage');
        $response->assertJsonPath('data.attributes.commissionRate', '0.1000');

        $this->assertDatabaseHas('programs', [
            'account_id' => $account->id,
            'slug' => 'acme-percentage',
            'commission_strategy' => 'percentage',
        ]);
    }

    #[Test]
    public function itRequiresACommissionRateWhenTheStrategyIsPercentage(): void
    {
        $account = $this->actingAsAccountMember();

        $response = $this->postJsonApi($account, 'programs', [
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
        $account = $this->actingAsAccountMember();

        Program::factory()->for($account)->create(['name' => 'Acme', 'slug' => 'acme']);

        $response = $this->postJsonApi($account, 'programs', [
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
        $account = $this->actingAsAccountMember();

        Program::factory()->for($account)->count(3)->create();

        $response = $this->getJsonApi("/api/v1/accounts/{$account->id}/programs");

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

    #[Test]
    public function itNeverReturnsAnotherAccountsPrograms(): void
    {
        $otherAccount = Account::factory()->create();
        Program::factory()->for($otherAccount)->count(2)->create();

        $account = $this->actingAsAccountMember();
        Program::factory()->for($account)->create();

        $response = $this->getJsonApi("/api/v1/accounts/{$account->id}/programs");

        $response->assertOk();
        $response->assertJsonCount(1, 'data');
    }

    #[Test]
    public function itReturns404ForAnAccountTheCallerIsNotAMemberOf(): void
    {
        $this->actingAsAccountMember();
        $otherAccount = Account::factory()->create();

        $response = $this->getJsonApi("/api/v1/accounts/{$otherAccount->id}/programs");

        $response->assertNotFound();
    }

    #[Test]
    public function itReturns401ForAnUnauthenticatedRequest(): void
    {
        $account = Account::factory()->create();

        $response = $this->getJsonApi("/api/v1/accounts/{$account->id}/programs");

        $response->assertUnauthorized();
    }
}
