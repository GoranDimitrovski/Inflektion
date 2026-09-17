<?php

declare(strict_types=1);

namespace App\Access;

use App\Models\Account;
use RuntimeException;

final class TenantContext
{
    private ?Account $account = null;

    private ?Role $role = null;

    public function set(Account $account, Role $role): void
    {
        $this->account = $account;
        $this->role = $role;
    }

    public function account(): Account
    {
        if ($this->account === null) {
            throw new RuntimeException('No tenant account has been resolved for this request.');
        }

        return $this->account;
    }

    public function role(): Role
    {
        if ($this->role === null) {
            throw new RuntimeException('No tenant account has been resolved for this request.');
        }

        return $this->role;
    }

    public function hasAccount(): bool
    {
        return $this->account !== null;
    }
}
