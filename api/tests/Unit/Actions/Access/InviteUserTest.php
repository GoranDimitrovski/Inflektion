<?php

declare(strict_types=1);

namespace Tests\Unit\Actions\Access;

use App\Access\Role;
use App\Actions\Access\InviteUser;
use App\Models\Account;
use App\Models\Invitation;
use App\Models\User;
use App\Notifications\InvitationReceived;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use LogicException;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class InviteUserTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function itCreatesAPendingInvitationAndNotifiesTheInvitee(): void
    {
        Notification::fake();

        $account = Account::factory()->create();
        $inviter = User::factory()->create();

        $invitation = app(InviteUser::class)->handle($account, $inviter, Role::Owner, 'new-member@example.com', Role::Member);

        $this->assertSame(Invitation::STATUS_PENDING, $invitation->status);
        $this->assertSame('new-member@example.com', $invitation->email);
        $this->assertTrue($invitation->isPending());

        Notification::assertSentOnDemand(InvitationReceived::class);

        $this->assertDatabaseHas('audit_entries', [
            'account_id' => $account->id,
            'actor_user_id' => $inviter->id,
            'action' => 'access.invitation_sent',
            'subject_type' => $invitation->getMorphClass(),
            'subject_id' => $invitation->id,
        ]);
    }

    #[Test]
    public function itRefusesToInviteSomeoneAtARoleHigherThanTheInvitersOwn(): void
    {
        $account = Account::factory()->create();
        $inviter = User::factory()->create();

        $this->expectException(LogicException::class);

        app(InviteUser::class)->handle($account, $inviter, Role::Admin, 'new-owner@example.com', Role::Owner);
    }
}
