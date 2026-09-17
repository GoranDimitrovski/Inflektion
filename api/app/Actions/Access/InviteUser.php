<?php

declare(strict_types=1);

namespace App\Actions\Access;

use App\Access\AuditEntry;
use App\Access\Role;
use App\Models\Account;
use App\Models\Invitation;
use App\Models\User;
use App\Notifications\InvitationReceived;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use LogicException;

final class InviteUser
{
    public function handle(Account $account, User $inviter, Role $inviterRole, string $email, Role $role): Invitation
    {

        if ($role->rank() > $inviterRole->rank()) {
            throw new LogicException('You cannot invite someone at a role higher than your own.');
        }

        $plainTextToken = Str::random(40);

        $invitation = DB::transaction(function () use ($account, $inviter, $email, $role, $plainTextToken): Invitation {
            $invitation = Invitation::create([
                'account_id' => $account->id,
                'email' => $email,
                'role' => $role,
                'invited_by_user_id' => $inviter->id,
                'token' => hash('sha256', $plainTextToken),
                'status' => Invitation::STATUS_PENDING,
                'expires_at' => now()->addDays(7),
            ]);

            AuditEntry::record($account->id, $inviter->id, 'access.invitation_sent', $invitation, ['email' => $email, 'role' => $role->value], now());

            return $invitation;
        });

        Notification::route('mail', $email)->notify(new InvitationReceived($invitation, $plainTextToken));

        return $invitation;
    }
}
