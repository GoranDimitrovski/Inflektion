<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Laravel\Fortify\Fortify;

final class TwoFactorSecretKeyController extends Controller
{
    /**
     * @response array{data: array{secretKey: string}}
     */
    public function show(Request $request): JsonResponse
    {
        $user = $request->user();

        if ($user->two_factor_secret === null) {
            abort(404);
        }

        return response()->json([
            'data' => [
                'secretKey' => Fortify::currentEncrypter()->decrypt($user->two_factor_secret),
            ],
        ]);
    }
}
