<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Access\AuditEntry;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Laravel\Fortify\Actions\GenerateNewRecoveryCodes;

final class TwoFactorRecoveryCodesController extends Controller
{
    public function index(Request $request): JsonResponse
    {

        $user = $request->user();

        if ($user->two_factor_secret === null || $user->two_factor_recovery_codes === null) {
            return response()->json(['data' => []]);
        }

        return response()->json(['data' => $user->recoveryCodes()]);
    }

    public function store(Request $request, GenerateNewRecoveryCodes $generate): JsonResponse
    {

        $user = $request->user();

        $generate($user);

        AuditEntry::record(null, $user->id, 'access.2fa_recovery_codes_regenerated', null, [], now());

        return response()->json(['data' => $user->recoveryCodes()]);
    }
}
