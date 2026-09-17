<?php

declare(strict_types=1);

namespace App\Actions\Access;

use App\Access\AuditEntry;
use App\Access\Role;
use App\Models\Membership;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Laravel\Fortify\Actions\DisableTwoFactorAuthentication as FortifyDisableTwoFactorAuthentication;
use LogicException;

final class DisableTwoFactorAuthentication
{
    public function __construct(
        private readonly FortifyDisableTwoFactorAuthentication $disable,
    ) {}

    public function handle(User $user): void
    {
        $holdsAnOwnerMembership = Membership::query()
            ->where('user_id', $user->id)
            ->where('role', Role::Owner)
            ->exists();

        if ($holdsAnOwnerMembership) {
            throw new LogicException('You cannot disable two-factor authentication while you are an Owner on an account.');
        }

        DB::transaction(function () use ($user): void {
            ($this->disable)($user);

            AuditEntry::record(null, $user->id, 'access.2fa_disabled', null, [], now());
        });
    }
}
