<?php

declare(strict_types=1);

namespace App\Policies;

use App\Access\ApiToken;
use App\Models\User;

final class ApiTokenPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function delete(User $user, ApiToken $token): bool
    {
        return $token->tokenable_id === $user->id && $token->tokenable_type === $user->getMorphClass();
    }
}
