<?php

declare(strict_types=1);

namespace App\Actions\Access;

use App\Access\AuditEntry;
use App\Access\Role;
use App\Models\Membership;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use LogicException;

final class RevokeAccess
{
    public function handle(Membership $target, User $actor, Role $actorRole): void
    {
        if ($target->isTheLastOwner()) {
            throw new LogicException('An account must always have at least one owner.');
        }

        if ($target->user_id !== $actor->id && $actorRole->rank() < $target->role->rank()) {
            throw new LogicException('You cannot remove a member who outranks you.');
        }

        DB::transaction(function () use ($target, $actor): void {

            AuditEntry::record($target->account_id, $actor->id, 'access.access_revoked', $target, [
                'role' => $target->role->value,
            ], now());

            $target->delete();
        });
    }
}
