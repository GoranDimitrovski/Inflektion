<?php

declare(strict_types=1);

namespace App\Actions\Access;

use App\Access\AuditEntry;
use App\Models\Invitation;
use App\Models\Membership;
use App\Models\User;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use LogicException;

final class AcceptInvitation
{
    public function handle(Invitation $invitation, User $user): Membership
    {
        if (! $invitation->isPending()) {
            throw new LogicException('This invitation is no longer valid.');
        }

        if (strcasecmp($invitation->email, $user->email) !== 0) {
            throw new LogicException("You're signed in as a different email than this invitation was sent to.");
        }

        try {
            return DB::transaction(function () use ($invitation, $user): Membership {
                $membership = Membership::create([
                    'account_id' => $invitation->account_id,
                    'user_id' => $user->id,
                    'role' => $invitation->role,
                ]);

                $invitation->accept(now());

                AuditEntry::record($invitation->account_id, $user->id, 'access.invitation_accepted', $membership, [], now());

                return $membership;
            });
        } catch (UniqueConstraintViolationException) {

            return Membership::query()
                ->where('account_id', $invitation->account_id)
                ->where('user_id', $user->id)
                ->firstOrFail();
        }
    }
}
