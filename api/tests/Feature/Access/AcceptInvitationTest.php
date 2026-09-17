<?php

declare(strict_types=1);

namespace Tests\Feature\Access;

use App\Access\Role;
use App\Models\Account;
use App\Models\Invitation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class AcceptInvitationTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function itShowsInvitationDetailsForAValidToken(): void
    {
        $account = Account::factory()->create(['name' => 'Acme Co']);
        [$invitation, $token] = $this->createInvitation($account, 'invitee@example.com');

        $response = $this->getJson("/api/invitations/{$token}");

        $response->assertOk();
        $response->assertJsonPath('data.accountName', 'Acme Co');
        $response->assertJsonPath('data.email', 'invitee@example.com');
        $response->assertJsonPath('data.userExists', false);
    }

    #[Test]
    public function itReturnsNotFoundForAnUnknownToken(): void
    {
        $response = $this->getJson('/api/invitations/not-a-real-token');

        $response->assertNotFound();
    }

    #[Test]
    public function aNewUserCanAcceptByRegistering(): void
    {
        $account = Account::factory()->create();
        [$invitation, $token] = $this->createInvitation($account, 'invitee@example.com', Role::Member);

        $response = $this->postJson("/api/invitations/{$token}/accept", [
            'name' => 'Ada Lovelace',
            'password' => 'a-strong-password',
            'password_confirmation' => 'a-strong-password',
        ], ['Origin' => 'http://localhost:4200']);

        $response->assertOk();
        $response->assertJsonPath('data.account.id', $account->id);
        $response->assertJsonPath('data.role', 'member');

        $this->assertDatabaseHas('users', ['email' => 'invitee@example.com']);
        $user = User::query()->where('email', 'invitee@example.com')->firstOrFail();
        $this->assertDatabaseHas('memberships', ['account_id' => $account->id, 'user_id' => $user->id]);
        $this->assertAuthenticatedAs($user);
        $this->assertDatabaseHas('audit_entries', [
            'account_id' => $account->id,
            'actor_user_id' => $user->id,
            'action' => 'access.invitation_accepted',
        ]);
    }

    #[Test]
    public function itRequiresNameAndPasswordForABrandNewUser(): void
    {
        $account = Account::factory()->create();
        [$invitation, $token] = $this->createInvitation($account, 'invitee@example.com');

        $response = $this->postJson("/api/invitations/{$token}/accept", [], ['Origin' => 'http://localhost:4200']);

        $response->assertUnprocessable();
    }

    #[Test]
    public function anExistingUserIsToldToLogInFirst(): void
    {
        $account = Account::factory()->create();
        [$invitation, $token] = $this->createInvitation($account, 'existing@example.com');
        User::factory()->create(['email' => 'existing@example.com']);

        $response = $this->postJson("/api/invitations/{$token}/accept", [], ['Origin' => 'http://localhost:4200']);

        $response->assertUnauthorized();
    }

    #[Test]
    public function anAuthenticatedUserWithTheMatchingEmailCanAccept(): void
    {
        $account = Account::factory()->create();
        [$invitation, $token] = $this->createInvitation($account, 'existing@example.com');
        $user = User::factory()->create(['email' => 'existing@example.com']);

        $response = $this->actingAs($user)->postJson("/api/invitations/{$token}/accept", [], ['Origin' => 'http://localhost:4200']);

        $response->assertOk();
        $this->assertDatabaseHas('memberships', ['account_id' => $account->id, 'user_id' => $user->id]);
    }

    #[Test]
    public function anAuthenticatedUserWithADifferentEmailIsRefused(): void
    {
        $account = Account::factory()->create();
        [$invitation, $token] = $this->createInvitation($account, 'invitee@example.com');
        $wrongUser = User::factory()->create(['email' => 'someone-else@example.com']);

        $response = $this->actingAs($wrongUser)->postJson("/api/invitations/{$token}/accept", [], ['Origin' => 'http://localhost:4200']);

        $response->assertUnprocessable();
    }

    #[Test]
    public function anExpiredInvitationIsRefused(): void
    {
        $account = Account::factory()->create();
        [$invitation, $token] = $this->createInvitation($account, 'invitee@example.com');
        $invitation->update(['expires_at' => now()->subDay()]);

        $response = $this->postJson("/api/invitations/{$token}/accept", [
            'name' => 'Ada Lovelace',
            'password' => 'a-strong-password',
            'password_confirmation' => 'a-strong-password',
        ], ['Origin' => 'http://localhost:4200']);

        $response->assertUnprocessable();
    }

    /**
     * @return array{0: Invitation, 1: string} the invitation and its plain-text token
     */
    private function createInvitation(Account $account, string $email, Role $role = Role::Member): array
    {
        $plainTextToken = Str::random(40);

        $invitation = Invitation::factory()->for($account)->create([
            'email' => $email,
            'role' => $role,
            'token' => hash('sha256', $plainTextToken),
        ]);

        return [$invitation, $plainTextToken];
    }
}
