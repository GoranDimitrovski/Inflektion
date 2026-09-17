<?php

declare(strict_types=1);

namespace App\Access;

final class PermissionMap
{
    /**
     * @var array<string, list<string>>
     */
    private const PERMISSIONS = [
        'owner' => ['programs.read', 'programs.write', 'members.manage'],
        'admin' => ['programs.read', 'programs.write', 'members.manage'],
        'member' => ['programs.read', 'programs.write'],
        'viewer' => ['programs.read'],
    ];

    /**
     * @return list<string>
     */
    public function permissionsFor(Role $role): array
    {
        return self::PERMISSIONS[$role->value];
    }
}
