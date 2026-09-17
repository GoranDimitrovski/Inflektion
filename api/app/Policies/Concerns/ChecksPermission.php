<?php

declare(strict_types=1);

namespace App\Policies\Concerns;

use App\Models\User;

trait ChecksPermission
{
    private function hasPermission(User $user, string $permission): bool
    {
        if (! $this->tenant->hasAccount()) {
            return false;
        }

        $hasRolePermission = in_array($permission, $this->permissions->permissionsFor($this->tenant->role()), true);

        return $hasRolePermission && $user->tokenCan($permission);
    }
}
