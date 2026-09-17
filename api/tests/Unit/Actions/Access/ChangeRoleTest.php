<?php

declare(strict_types=1);

namespace Tests\Unit\Actions\Access;

use App\Access\Role;
use App\Actions\Access\ChangeRole;
use App\Models\Account;
use App\Models\Membership;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use LogicException;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class ChangeRoleTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function itChangesAMembersRole(): void
    {
        $account = Account::factory()->create();
        $target = Membership::factory()->for($account)->create(['role' => Role::Member]);
        $actor = User::factory()->create();

        $updated = app(ChangeRole::class)->handle($target, Role::Admin, $actor, Role::Owner);

        $this->assertSame(Role::Admin, $updated->fresh()->role);
        $this->assertDatabaseHas('audit_entries', [
            'account_id' => $account->id,
            'actor_user_id' => $actor->id,
            'action' => 'access.role_changed',
            'subject_type' => $target->getMorphClass(),
            'subject_id' => $target->id,
        ]);
    }

    #[Test]
    public function itRefusesToChangeTheActorsOwnRole(): void
    {
        $account = Account::factory()->create();
        $actor = User::factory()->create();
        $target = Membership::factory()->for($account)->create(['user_id' => $actor->id, 'role' => Role::Member]);

        $this->expectException(LogicException::class);

        app(ChangeRole::class)->handle($target, Role::Admin, $actor, Role::Owner);
    }

    #[Test]
    public function itRefusesToGrantARoleHigherThanTheActorsOwn(): void
    {
        $account = Account::factory()->create();
        $target = Membership::factory()->for($account)->create(['role' => Role::Member]);
        $actor = User::factory()->create();

        $this->expectException(LogicException::class);

        app(ChangeRole::class)->handle($target, Role::Owner, $actor, Role::Admin);
    }

    #[Test]
    public function itRefusesToDemoteTheLastOwner(): void
    {
        $account = Account::factory()->create();
        $target = Membership::factory()->for($account)->create(['role' => Role::Owner]);
        $actor = User::factory()->create();

        $this->expectException(LogicException::class);

        app(ChangeRole::class)->handle($target, Role::Admin, $actor, Role::Owner);
    }

    #[Test]
    public function itAllowsDemotingAnOwnerWhenAnotherOwnerRemains(): void
    {
        $account = Account::factory()->create();
        Membership::factory()->for($account)->create(['role' => Role::Owner]);
        $target = Membership::factory()->for($account)->create(['role' => Role::Owner]);
        $actor = User::factory()->create();

        $updated = app(ChangeRole::class)->handle($target, Role::Admin, $actor, Role::Owner);

        $this->assertSame(Role::Admin, $updated->fresh()->role);
    }
}
