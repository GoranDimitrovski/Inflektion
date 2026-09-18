<?php

declare(strict_types=1);

namespace Tests\Feature\Access;

use App\Access\Role;
use App\Models\Invitation;
use App\Notifications\InvitationReceived;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Notification;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class InvitationsTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function anOwnerCanInviteSomeone(): void
    {
        Notification::fake();

        $account = $this->actingAsAccountMember(role: Role::Owner);

        $response = $this->postJsonApi($account, 'invitations', ['email' => 'invitee@example.com', 'role' => 'member']);

        $response->assertCreated();
        $response->assertJsonPath('data.attributes.email', 'invitee@example.com');
        $response->assertJsonPath('data.attributes.role', 'member');
        $response->assertJsonPath('data.attributes.status', 'pending');

        Notification::assertSentOnDemand(InvitationReceived::class);
        $this->assertDatabaseHas('audit_entries', [
            'account_id' => $account->id,
            'actor_user_id' => Auth::id(),
            'action' => 'access.invitation_sent',
        ]);
    }

    #[Test]
    public function aMemberCannotInviteSomeone(): void
    {
        $account = $this->actingAsAccountMember(role: Role::Member);

        $response = $this->postJsonApi($account, 'invitations', ['email' => 'invitee@example.com', 'role' => 'member']);

        $response->assertForbidden();
    }

    #[Test]
    public function anAdminCannotInviteSomeoneAsOwner(): void
    {
        $account = $this->actingAsAccountMember(role: Role::Admin);

        $response = $this->postJsonApi($account, 'invitations', ['email' => 'invitee@example.com', 'role' => 'owner']);

        $response->assertUnprocessable();
    }

    #[Test]
    public function itListsPendingInvitations(): void
    {
        $account = $this->actingAsAccountMember(role: Role::Owner);
        Invitation::factory()->for($account)->count(2)->create();

        $response = $this->getJsonApi("/api/v1/accounts/{$account->id}/invitations");

        $response->assertOk();
        $response->assertJsonCount(2, 'data');
    }

    #[Test]
    public function anOwnerCanRevokeAPendingInvitation(): void
    {
        $account = $this->actingAsAccountMember(role: Role::Owner);
        $invitation = Invitation::factory()->for($account)->create();

        $response = $this->deleteJson("/api/v1/accounts/{$account->id}/invitations/{$invitation->id}", [], [
            'Accept' => self::JSON_API_MEDIA_TYPE,
        ]);

        $response->assertNoContent();
        $this->assertDatabaseHas('invitations', ['id' => $invitation->id, 'status' => Invitation::STATUS_REVOKED]);
        $this->assertDatabaseHas('audit_entries', [
            'account_id' => $account->id,
            'actor_user_id' => Auth::id(),
            'action' => 'access.invitation_revoked',
            'subject_id' => $invitation->id,
        ]);
    }

    #[Test]
    public function revokingAnAlreadyRevokedInvitationFails(): void
    {
        $account = $this->actingAsAccountMember(role: Role::Owner);
        $invitation = Invitation::factory()->for($account)->create(['status' => Invitation::STATUS_REVOKED]);

        $response = $this->deleteJson("/api/v1/accounts/{$account->id}/invitations/{$invitation->id}", [], [
            'Accept' => self::JSON_API_MEDIA_TYPE,
        ]);

        $response->assertUnprocessable();
    }
}
