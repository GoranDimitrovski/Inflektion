<?php

declare(strict_types=1);

namespace Tests\Unit\Actions\Access;

use App\Access\Role;
use App\Actions\Access\RevokeAccess;
use App\Models\Account;
use App\Models\Membership;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use LogicException;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class RevokeAccessTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function itRemovesAMembersAccess(): void
    {
        $account = Account::factory()->create();
        $target = Membership::factory()->for($account)->create(['role' => Role::Member]);
        $actor = User::factory()->create();

        app(RevokeAccess::class)->handle($target, $actor, Role::Owner);

        $this->assertDatabaseMissing('memberships', ['id' => $target->id]);
        $this->assertDatabaseHas('audit_entries', [
            'account_id' => $account->id,
            'actor_user_id' => $actor->id,
            'action' => 'access.access_revoked',
            'subject_type' => $target->getMorphClass(),
            'subject_id' => $target->id,
        ]);
    }

    #[Test]
    public function aMemberMayLeaveOnTheirOwn(): void
    {
        $account = Account::factory()->create();
        $actor = User::factory()->create();
        $target = Membership::factory()->for($account)->create(['user_id' => $actor->id, 'role' => Role::Viewer]);

        app(RevokeAccess::class)->handle($target, $actor, Role::Viewer);

        $this->assertDatabaseMissing('memberships', ['id' => $target->id]);
    }

    #[Test]
    public function itRefusesToRemoveTheLastOwnerEvenWhenTheyAreLeavingThemselves(): void
    {
        $account = Account::factory()->create();
        $actor = User::factory()->create();
        $target = Membership::factory()->for($account)->create(['user_id' => $actor->id, 'role' => Role::Owner]);

        $this->expectException(LogicException::class);

        app(RevokeAccess::class)->handle($target, $actor, Role::Owner);
    }

    #[Test]
    public function itRefusesToRemoveAMemberWhoOutranksTheActor(): void
    {
        $account = Account::factory()->create();
        Membership::factory()->for($account)->create(['role' => Role::Owner]);
        $target = Membership::factory()->for($account)->create(['role' => Role::Admin]);
        $actor = User::factory()->create();

        $this->expectException(LogicException::class);

        app(RevokeAccess::class)->handle($target, $actor, Role::Member);
    }
}
