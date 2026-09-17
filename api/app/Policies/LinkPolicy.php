<?php

declare(strict_types=1);

namespace App\Policies;

use App\Access\PermissionMap;
use App\Access\TenantContext;
use App\Models\User;
use App\Policies\Concerns\ChecksPermission;

final class LinkPolicy
{
    use ChecksPermission;

    public function __construct(
        private readonly TenantContext $tenant,
        private readonly PermissionMap $permissions,
    ) {}

    public function viewAny(User $user): bool
    {
        return $this->hasPermission($user, 'programs.read');
    }

    public function create(User $user): bool
    {
        return $this->hasPermission($user, 'programs.write');
    }

    public function update(User $user): bool
    {
        return $this->hasPermission($user, 'programs.write');
    }
}
