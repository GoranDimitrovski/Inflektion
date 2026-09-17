<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Access\PermissionMap;
use App\Access\Role;
use App\Http\Controllers\Controller;
use App\Models\Membership;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class MeController extends Controller
{
    public function __construct(
        private readonly PermissionMap $permissions,
    ) {}

    public function __invoke(Request $request): JsonResponse
    {

        $user = $request->user();

        $memberships = $user->memberships()
            ->with('account')
            ->get()
            ->map(fn (Membership $membership): array => [
                'account' => [
                    'id' => $membership->account->id,
                    'name' => $membership->account->name,
                    'slug' => $membership->account->slug,
                ],
                'role' => $membership->role->value,
                'permissions' => $this->permissions->permissionsFor($membership->role),

                'twoFactorRequired' => $membership->role === Role::Owner,
            ]);

        return response()->json([
            'data' => [
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'twoFactorEnabled' => $user->two_factor_confirmed_at !== null,
                ],
                'memberships' => $memberships,
            ],
        ]);
    }
}
