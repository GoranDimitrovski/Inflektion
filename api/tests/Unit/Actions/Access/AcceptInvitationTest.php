<?php

declare(strict_types=1);

namespace Tests\Unit\Actions\Access;

use App\Access\Role;
use App\Actions\Access\AcceptInvitation;
use App\Models\Account;
use App\Models\Invitation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use LogicException;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class AcceptInvitationTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function itCreatesAMembershipAndMarksTheInvitationAccepted(): void
    {
        $account = Account::factory()->create();
        $invitation = Invitation::factory()->for($account)->create(['email' => 'invitee@example.com', 'role' => Role::Member]);
        $user = User::factory()->create(['email' => 'invitee@example.com']);

        $membership = app(AcceptInvitation::class)->handle($invitation, $user);

        $this->assertSame($account->id, $membership->account_id);
        $this->assertSame($user->id, $membership->user_id);
        $this->assertSame(Role::Member, $membership->role);
        $this->assertSame(Invitation::STATUS_ACCEPTED, $invitation->fresh()->status);

        $this->assertDatabaseHas('audit_entries', [
            'account_id' => $account->id,
            'actor_user_id' => $user->id,
            'action' => 'access.invitation_accepted',
            'subject_type' => $membership->getMorphClass(),
            'subject_id' => $membership->id,
        ]);
    }

    #[Test]
    public function itRefusesWhenTheSignedInUsersEmailDoesNotMatchTheInvitation(): void
    {
        $invitation = Invitation::factory()->create(['email' => 'invitee@example.com']);
        $wrongUser = User::factory()->create(['email' => 'someone-else@example.com']);

        $this->expectException(LogicException::class);

        app(AcceptInvitation::class)->handle($invitation, $wrongUser);
    }

    #[Test]
    public function itRefusesAnAlreadyAcceptedInvitation(): void
    {
        $invitation = Invitation::factory()->create(['email' => 'invitee@example.com', 'status' => Invitation::STATUS_ACCEPTED]);
        $user = User::factory()->create(['email' => 'invitee@example.com']);

        $this->expectException(LogicException::class);

        app(AcceptInvitation::class)->handle($invitation, $user);
    }

    #[Test]
    public function itRefusesAnExpiredInvitation(): void
    {
        $invitation = Invitation::factory()->create([
            'email' => 'invitee@example.com',
            'expires_at' => now()->subDay(),
        ]);
        $user = User::factory()->create(['email' => 'invitee@example.com']);

        $this->expectException(LogicException::class);

        app(AcceptInvitation::class)->handle($invitation, $user);
    }

    #[Test]
    public function acceptingTwiceIsIdempotentRatherThanAHardFailure(): void
    {
        $account = Account::factory()->create();
        $invitation = Invitation::factory()->for($account)->create(['email' => 'invitee@example.com', 'role' => Role::Member]);
        $user = User::factory()->create(['email' => 'invitee@example.com']);

        $first = app(AcceptInvitation::class)->handle($invitation, $user);

        $invitation->status = Invitation::STATUS_PENDING;
        $second = app(AcceptInvitation::class)->handle($invitation, $user);

        $this->assertSame($first->id, $second->id);
        $this->assertDatabaseCount('memberships', 1);
    }
}
