<?php

declare(strict_types=1);

namespace App\JsonApi\V1\Memberships;

use App\Access\Role;
use Illuminate\Validation\Rule;
use LaravelJsonApi\Laravel\Http\Requests\ResourceRequest;

class MembershipRequest extends ResourceRequest
{
    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'role' => ['required', 'string', Rule::in(array_map(fn (Role $role): string => $role->value, Role::cases()))],
        ];
    }
}
