<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Access\AuditEntry;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\ResetPasswordRequest;
use App\Models\User;
use Illuminate\Auth\Passwords\PasswordBroker;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\ValidationException;

final class ResetPasswordController extends Controller
{
    public function __invoke(ResetPasswordRequest $request): Response
    {
        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function (User $user, string $password): void {
                $user->forceFill(['password' => Hash::make($password)])->save();

                $user->tokens()->delete();

                AuditEntry::record(null, $user->id, 'auth.password_reset', null, [], now());
            },
        );

        if ($status !== PasswordBroker::PASSWORD_RESET) {
            throw ValidationException::withMessages([
                'email' => [__($status)],
            ]);
        }

        return response()->noContent();
    }
}
