<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Access\PermissionMap;
use App\Actions\Access\AcceptInvitation;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\AcceptInvitationRequest;
use App\Models\Invitation;
use App\Models\User;
use Dedoc\Scramble\Attributes\BodyParameter;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use LogicException;

final class AcceptInvitationController extends Controller
{
    public function __construct(
        private readonly PermissionMap $permissions,
    ) {}

    // Declared explicitly because the guest branch below validates these as
    // `required`; inferring from that would mark them required for the
    // signed-in caller too, who sends an empty body.
    #[BodyParameter(name: 'name', description: 'Only when accepting as a new user.', required: false, type: 'string')]
    #[BodyParameter(name: 'password', description: 'Only when accepting as a new user.', required: false, type: 'string')]
    #[BodyParameter(name: 'password_confirmation', description: 'Only when accepting as a new user.', required: false, type: 'string')]
    public function __invoke(AcceptInvitationRequest $request, string $token): JsonResponse
    {
        $invitation = Invitation::query()
            ->where('token', hash('sha256', $token))
            ->first();

        if ($invitation === null) {
            throw ValidationException::withMessages(['token' => 'This invitation is no longer valid.']);
        }

        $user = $request->user();

        if ($user === null) {
            $existingUser = User::query()->where('email', $invitation->email)->first();

            if ($existingUser !== null) {

                return response()->json([
                    'message' => 'An account with this email already exists. Log in, then open this invitation link again.',
                ], 401);
            }

            $request->validate([
                'name' => ['required', 'string', 'max:255'],
                'password' => ['required', 'string', 'min:8', 'confirmed'],
            ]);

            $user = User::create([
                'name' => $request->string('name')->toString(),
                'email' => $invitation->email,
                'password' => Hash::make($request->string('password')->toString()),
            ]);

            Auth::login($user);
            $request->session()->regenerate();
        }

        try {
            $membership = app(AcceptInvitation::class)->handle($invitation, $user);
        } catch (LogicException $e) {
            throw ValidationException::withMessages(['token' => $e->getMessage()]);
        }

        return response()->json([
            'data' => [
                'account' => [
                    'id' => $invitation->account->id,
                    'name' => $invitation->account->name,
                    'slug' => $invitation->account->slug,
                ],
                'role' => $membership->role->value,
                'permissions' => $this->permissions->permissionsFor($membership->role),
            ],
        ]);
    }
}
