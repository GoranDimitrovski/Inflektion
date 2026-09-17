<?php

declare(strict_types=1);

namespace Tests\Feature\Auth;

use App\Access\Role;
use App\Models\Account;
use App\Models\Membership;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class MeTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function itReturnsTheAuthenticatedUserAndResolvedPermissionsPerMembership(): void
    {
        $account = $this->actingAsAccountMember(role: Role::Viewer);

        $response = $this->getJson('/api/me');

        $response->assertOk();
        $response->assertJsonPath('data.memberships.0.account.id', $account->id);
        $response->assertJsonPath('data.memberships.0.role', 'viewer');
        $response->assertJsonPath('data.memberships.0.permissions', ['programs.read']);
        $response->assertJsonPath('data.memberships.0.twoFactorRequired', false);
        $response->assertJsonPath('data.user.twoFactorEnabled', false);
    }

    #[Test]
    public function itListsEveryAccountTheUserBelongsTo(): void
    {
        $user = User::factory()->create();
        $accountA = Account::factory()->create();
        $accountB = Account::factory()->create();

        Membership::factory()->create(['user_id' => $user->id, 'account_id' => $accountA->id, 'role' => Role::Owner]);
        Membership::factory()->create(['user_id' => $user->id, 'account_id' => $accountB->id, 'role' => Role::Viewer]);

        $response = $this->actingAs($user)->getJson('/api/me');

        $response->assertOk();
        $response->assertJsonCount(2, 'data.memberships');
        $accountIds = array_column($response->json('data.memberships'), 'account');
        $this->assertEqualsCanonicalizing(
            [$accountA->id, $accountB->id],
            array_column($accountIds, 'id'),
        );
    }

    #[Test]
    public function twoFactorIsRequiredOnlyForAnOwnerMembership(): void
    {
        $user = User::factory()->create();
        $accountA = Account::factory()->create();
        $accountB = Account::factory()->create();

        Membership::factory()->create(['user_id' => $user->id, 'account_id' => $accountA->id, 'role' => Role::Owner]);
        Membership::factory()->create(['user_id' => $user->id, 'account_id' => $accountB->id, 'role' => Role::Admin]);

        $response = $this->actingAs($user)->getJson('/api/me');

        $memberships = collect($response->json('data.memberships'))->keyBy(fn (array $m): int => $m['account']['id']);

        $this->assertTrue($memberships[$accountA->id]['twoFactorRequired']);
        $this->assertFalse($memberships[$accountB->id]['twoFactorRequired']);
    }

    #[Test]
    public function itRequiresAuthentication(): void
    {
        $response = $this->getJson('/api/me');

        $response->assertUnauthorized();
    }
}
