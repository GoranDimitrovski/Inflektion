<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Access\AuditEntry;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Laravel\Fortify\Actions\ConfirmTwoFactorAuthentication;

final class ConfirmedTwoFactorAuthenticationController extends Controller
{
    public function store(Request $request, ConfirmTwoFactorAuthentication $confirm): Response
    {
        $validated = $request->validate(['code' => ['required', 'string']]);

        $user = $request->user();

        $confirm($user, $validated['code']);

        AuditEntry::record(null, $user->id, 'access.2fa_confirmed', null, [], now());

        return response()->noContent();
    }
}
