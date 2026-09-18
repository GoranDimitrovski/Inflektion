<?php

declare(strict_types=1);

namespace Tests\Feature\Access;

use App\Access\Role;
use App\Models\Membership;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class MembershipsTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function anOwnerCanListMembers(): void
    {
        $account = $this->actingAsAccountMember(role: Role::Owner);
        Membership::factory()->for($account)->create(['role' => Role::Member]);

        $response = $this->getJsonApi("/api/v1/accounts/{$account->id}/memberships");

        $response->assertOk();
        $response->assertJsonCount(2, 'data');
    }

    #[Test]
    public function aMemberCannotListMemberships(): void
    {
        $account = $this->actingAsAccountMember(role: Role::Member);

        $response = $this->getJsonApi("/api/v1/accounts/{$account->id}/memberships");

        $response->assertForbidden();
    }

    #[Test]
    public function anOwnerCanChangeAnotherMembersRole(): void
    {
        $account = $this->actingAsAccountMember(role: Role::Owner);
        $target = Membership::factory()->for($account)->create(['role' => Role::Member]);

        $response = $this->patchJson("/api/v1/accounts/{$account->id}/memberships/{$target->id}", [
            'data' => [
                'type' => 'memberships',
                'id' => (string) $target->id,
                'attributes' => ['role' => 'admin'],
            ],
        ], $this->jsonApiHeaders());

        $response->assertOk();
        $response->assertJsonPath('data.attributes.role', 'admin');
        $this->assertDatabaseHas('memberships', ['id' => $target->id, 'role' => 'admin']);
        $this->assertDatabaseHas('audit_entries', [
            'account_id' => $account->id,
            'actor_user_id' => Auth::id(),
            'action' => 'access.role_changed',
            'subject_id' => $target->id,
        ]);
    }

    #[Test]
    public function anAdminCannotPromoteSomeoneToOwner(): void
    {
        $account = $this->actingAsAccountMember(role: Role::Admin);
        $target = Membership::factory()->for($account)->create(['role' => Role::Member]);

        $response = $this->patchJson("/api/v1/accounts/{$account->id}/memberships/{$target->id}", [
            'data' => [
                'type' => 'memberships',
                'id' => (string) $target->id,
                'attributes' => ['role' => 'owner'],
            ],
        ], $this->jsonApiHeaders());

        $response->assertUnprocessable();
    }

    #[Test]
    public function anOwnerCanRemoveAnotherMember(): void
    {
        $account = $this->actingAsAccountMember(role: Role::Owner);
        $target = Membership::factory()->for($account)->create(['role' => Role::Member]);

        $response = $this->deleteJson("/api/v1/accounts/{$account->id}/memberships/{$target->id}", [], [
            'Accept' => self::JSON_API_MEDIA_TYPE,
        ]);

        $response->assertNoContent();
        $this->assertDatabaseMissing('memberships', ['id' => $target->id]);
        $this->assertDatabaseHas('audit_entries', [
            'account_id' => $account->id,
            'actor_user_id' => Auth::id(),
            'action' => 'access.access_revoked',
            'subject_id' => $target->id,
        ]);
    }

    #[Test]
    public function theSoleOwnerCannotRemoveThemselves(): void
    {
        $account = $this->actingAsAccountMember(role: Role::Owner);
        $ownMembership = Membership::query()->where('account_id', $account->id)->firstOrFail();

        $response = $this->deleteJson("/api/v1/accounts/{$account->id}/memberships/{$ownMembership->id}", [], [
            'Accept' => self::JSON_API_MEDIA_TYPE,
        ]);

        $response->assertUnprocessable();
        $this->assertDatabaseHas('memberships', ['id' => $ownMembership->id]);
    }
}
