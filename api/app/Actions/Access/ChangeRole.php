<?php

declare(strict_types=1);

namespace App\Actions\Access;

use App\Access\AuditEntry;
use App\Access\Role;
use App\Models\Membership;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use LogicException;

final class ChangeRole
{
    public function handle(Membership $target, Role $newRole, User $actor, Role $actorRole): Membership
    {
        if ($target->user_id === $actor->id) {
            throw new LogicException('You cannot change your own role. Leave the account instead if you want to remove your own access.');
        }

        if ($newRole->rank() > $actorRole->rank()) {
            throw new LogicException('You cannot grant a role higher than your own.');
        }

        if ($target->role === Role::Owner && $newRole !== Role::Owner && $target->isTheLastOwner()) {
            throw new LogicException('An account must always have at least one owner.');
        }

        return DB::transaction(function () use ($target, $newRole, $actor): Membership {
            $fromRole = $target->role;

            $target->update(['role' => $newRole]);

            AuditEntry::record($target->account_id, $actor->id, 'access.role_changed', $target, [
                'from' => $fromRole->value,
                'to' => $newRole->value,
            ], now());

            return $target;
        });
    }
}
