<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Access\AuditEntry;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;

final class LoginController extends Controller
{
    public function __invoke(LoginRequest $request): Response
    {
        $credentials = $request->only('email', 'password');
        $guard = Auth::guard('web');

        if (! $guard->validate($credentials)) {
            AuditEntry::record(null, null, 'auth.login_failed', null, ['email' => $request->string('email')->toString()], now());

            throw ValidationException::withMessages([
                'email' => [__('auth.failed')],
            ]);
        }

        /** @var User $user */
        $user = $guard->getProvider()->retrieveByCredentials($credentials);

        if ($user->two_factor_confirmed_at !== null) {
            $request->session()->put(['login.id' => $user->getKey(), 'login.remember' => false]);

            return response()->json(['data' => ['twoFactorRequired' => true]]);
        }

        $guard->login($user);
        $request->session()->regenerate();

        AuditEntry::record(null, $user->id, 'auth.login', null, [], now());

        return response()->noContent();
    }
}
