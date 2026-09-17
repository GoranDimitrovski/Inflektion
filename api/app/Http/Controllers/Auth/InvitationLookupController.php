<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Invitation;
use App\Models\User;
use Illuminate\Http\JsonResponse;

final class InvitationLookupController extends Controller
{
    public function __invoke(string $token): JsonResponse
    {
        $invitation = Invitation::query()
            ->where('token', hash('sha256', $token))
            ->first();

        if ($invitation === null || ! $invitation->isPending()) {
            return response()->json(['message' => 'This invitation is no longer valid.'], 404);
        }

        return response()->json([
            'data' => [
                'accountName' => $invitation->account->name,
                'email' => $invitation->email,
                'role' => $invitation->role->value,
                'userExists' => User::query()->where('email', $invitation->email)->exists(),
            ],
        ]);
    }
}
