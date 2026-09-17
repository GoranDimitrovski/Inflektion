<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Access\AuditEntry;
use App\Http\Controllers\Controller;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Laravel\Fortify\Contracts\FailedTwoFactorLoginResponse;
use Laravel\Fortify\Http\Requests\TwoFactorLoginRequest;

final class TwoFactorChallengeController extends Controller
{
    public function __invoke(TwoFactorLoginRequest $request): Response
    {

        $user = $request->challengedUser();

        if ($recoveryCode = $request->validRecoveryCode()) {
            $user->replaceRecoveryCode($recoveryCode);
        } elseif (! $request->hasValidCode()) {

            app(FailedTwoFactorLoginResponse::class)->toResponse($request);
        }

        Auth::guard('web')->login($user, $request->remember());
        $request->session()->regenerate();

        AuditEntry::record(null, $user->id, 'auth.login', null, ['via' => 'two_factor'], now());

        return response()->noContent();
    }
}
