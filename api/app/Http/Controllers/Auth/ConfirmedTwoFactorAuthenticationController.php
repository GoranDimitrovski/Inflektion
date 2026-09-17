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

        $user = $request->user();

        $confirm($user, (string) $request->input('code'));

        AuditEntry::record(null, $user->id, 'access.2fa_confirmed', null, [], now());

        return response()->noContent();
    }
}
