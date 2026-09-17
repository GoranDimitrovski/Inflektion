<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Access\AuditEntry;
use App\Actions\Access\DisableTwoFactorAuthentication;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Validation\ValidationException;
use Laravel\Fortify\Actions\EnableTwoFactorAuthentication;
use LogicException;

final class TwoFactorAuthenticationController extends Controller
{
    public function store(Request $request, EnableTwoFactorAuthentication $enable): Response
    {

        $user = $request->user();

        $enable($user);

        AuditEntry::record(null, $user->id, 'access.2fa_enabled', null, [], now());

        return response()->noContent();
    }

    public function destroy(Request $request, DisableTwoFactorAuthentication $disable): Response
    {

        $user = $request->user();

        try {
            $disable->handle($user);
        } catch (LogicException $e) {
            throw ValidationException::withMessages(['two_factor' => $e->getMessage()]);
        }

        return response()->noContent();
    }
}
