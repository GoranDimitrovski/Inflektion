<?php

declare(strict_types=1);

namespace App\Policies;

use App\Access\PermissionMap;
use App\Access\TenantContext;
use App\Models\User;
use App\Policies\Concerns\ChecksPermission;

final class InvitationPolicy
{
    use ChecksPermission;

    public function __construct(
        private readonly TenantContext $tenant,
        private readonly PermissionMap $permissions,
    ) {}

    public function viewAny(User $user): bool
    {
        return $this->hasPermission($user, 'members.manage');
    }

    public function create(User $user): bool
    {
        return $this->hasPermission($user, 'members.manage');
    }

    public function delete(User $user): bool
    {
        return $this->hasPermission($user, 'members.manage');
    }
}
