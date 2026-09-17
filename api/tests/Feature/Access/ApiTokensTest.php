<?php

declare(strict_types=1);

namespace Tests\Feature\Access;

use App\Access\Role;
use App\Actions\Access\IssueApiToken;
use App\Models\Account;
use App\Models\Membership;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use Laravel\Sanctum\NewAccessToken;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class ApiTokensTest extends TestCase
{
    use RefreshDatabase;

    private const JSON_API_MEDIA_TYPE = 'application/vnd.api+json';

    #[Test]
    public function aMemberCanIssueATokenForTheirOwnAccountAndSeesThePlaintextOnlyOnce(): void
    {
        $account = $this->actingAsAccountMember(role: Role::Admin);

        $response = $this->postJsonApi($account, ['name' => 'CI script', 'abilities' => ['programs.read']]);

        $response->assertCreated();
        $response->assertJsonPath('data.attributes.name', 'CI script');
        $response->assertJsonPath('data.attributes.abilities', ['programs.read']);
        $this->assertIsString($response->json('meta.plainTextToken'));
    }

    #[Test]
    public function aViewerCannotIssueATokenWithAnAbilityTheyDoNotHave(): void
    {
        $account = $this->actingAsAccountMember(role: Role::Viewer);

        $response = $this->postJsonApi($account, ['name' => 'Escalation', 'abilities' => ['programs.write']]);

        $response->assertUnprocessable();
    }

    #[Test]
    public function itListsOnlyTheCallersOwnTokensForThisAccount(): void
    {
        $account = $this->actingAsAccountMember(role: Role::Owner);
        $me = Membership::query()->where('account_id', $account->id)->firstOrFail()->user;

        $other = User::factory()->create();
        Membership::factory()->create(['account_id' => $account->id, 'user_id' => $other->id, 'role' => Role::Owner]);
        $this->issueToken($account, $other, Role::Owner, ['programs.read']);

        $this->issueToken($account, $me, Role::Owner, ['programs.read']);

        $response = $this->getJson("/api/v1/accounts/{$account->id}/api-tokens", ['Accept' => self::JSON_API_MEDIA_TYPE]);

        $response->assertOk();
        $response->assertJsonCount(1, 'data');
    }

    #[Test]
    public function theOwnerCanRevokeTheirOwnToken(): void
    {
        $account = $this->actingAsAccountMember(role: Role::Owner);
        $me = Membership::query()->where('account_id', $account->id)->firstOrFail()->user;
        $issued = $this->issueToken($account, $me, Role::Owner, ['programs.read']);

        $response = $this->deleteJson("/api/v1/accounts/{$account->id}/api-tokens/{$issued->accessToken->id}", [], [
            'Accept' => self::JSON_API_MEDIA_TYPE,
        ]);

        $response->assertNoContent();
        $this->assertDatabaseMissing('personal_access_tokens', ['id' => $issued->accessToken->id]);
        $this->assertDatabaseHas('audit_entries', [
            'account_id' => $account->id,
            'action' => 'access.token_revoked',
            'subject_id' => $issued->accessToken->id,
        ]);
    }

    #[Test]
    public function aMemberCannotRevokeSomeoneElsesToken(): void
    {
        $account = Account::factory()->create();
        $owner = User::factory()->create();
        Membership::factory()->create(['account_id' => $account->id, 'user_id' => $owner->id, 'role' => Role::Owner]);
        $ownerToken = $this->issueToken($account, $owner, Role::Owner, ['programs.read']);

        $this->actingAsAccountMember($account, Role::Owner);

        $response = $this->deleteJson("/api/v1/accounts/{$account->id}/api-tokens/{$ownerToken->accessToken->id}", [], [
            'Accept' => self::JSON_API_MEDIA_TYPE,
        ]);

        $response->assertNotFound();
        $this->assertDatabaseHas('personal_access_tokens', ['id' => $ownerToken->accessToken->id]);
    }

    #[Test]
    public function aTokenScopedToOneAccountCannotReachAnother(): void
    {
        $accountA = Account::factory()->create();
        $accountB = Account::factory()->create();
        $user = User::factory()->create();
        Membership::factory()->create(['account_id' => $accountA->id, 'user_id' => $user->id, 'role' => Role::Owner]);
        Membership::factory()->create(['account_id' => $accountB->id, 'user_id' => $user->id, 'role' => Role::Owner]);

        $token = app(IssueApiToken::class)->handle($accountA, $user, Role::Owner, 'Scoped', ['programs.read'], null);

        $response = $this->withToken($token->plainTextToken)
            ->getJson("/api/v1/accounts/{$accountB->id}/programs", ['Accept' => self::JSON_API_MEDIA_TYPE]);

        $response->assertNotFound();
    }

    #[Test]
    public function aTokenIsRefusedForAnAbilityItWasNotIssuedWithEvenThoughTheRoleAllowsIt(): void
    {
        $account = Account::factory()->create();
        $user = User::factory()->create();
        Membership::factory()->create(['account_id' => $account->id, 'user_id' => $user->id, 'role' => Role::Owner]);

        $token = app(IssueApiToken::class)->handle($account, $user, Role::Owner, 'Read only', ['programs.read'], null);

        $readResponse = $this->withToken($token->plainTextToken)
            ->getJson("/api/v1/accounts/{$account->id}/programs", ['Accept' => self::JSON_API_MEDIA_TYPE]);
        $readResponse->assertOk();

        $writeResponse = $this->withToken($token->plainTextToken)->postJson(
            "/api/v1/accounts/{$account->id}/programs",
            ['data' => ['type' => 'programs', 'attributes' => ['name' => 'Acme', 'slug' => 'acme']]],
            ['CONTENT_TYPE' => self::JSON_API_MEDIA_TYPE, 'Accept' => self::JSON_API_MEDIA_TYPE],
        );

        $writeResponse->assertForbidden();
    }

    private function issueToken(Account $account, User $user, Role $role, array $abilities): NewAccessToken
    {
        return app(IssueApiToken::class)->handle($account, $user, $role, 'Test token', $abilities, null);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function postJsonApi(Account $account, array $attributes): TestResponse
    {
        return $this->postJson("/api/v1/accounts/{$account->id}/api-tokens", [
            'data' => [
                'type' => 'api-tokens',
                'attributes' => $attributes,
            ],
        ], [
            'CONTENT_TYPE' => self::JSON_API_MEDIA_TYPE,
            'Accept' => self::JSON_API_MEDIA_TYPE,
        ]);
    }
}
