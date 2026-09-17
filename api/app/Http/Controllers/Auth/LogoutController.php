<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Access\AuditEntry;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;

final class LogoutController extends Controller
{
    public function __invoke(Request $request): Response
    {

        $user = $request->user();
        AuditEntry::record(null, $user->id, 'auth.logout', null, [], now());

        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return response()->noContent();
    }
}
