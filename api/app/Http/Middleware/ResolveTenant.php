<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Access\ApiToken;
use App\Access\TenantContext;
use App\Models\Account;
use App\Models\Membership;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class ResolveTenant
{
    public function handle(Request $request, Closure $next): Response
    {
        $routeAccount = $request->route('account');

        $account = $routeAccount instanceof Account
            ? $routeAccount
            : Account::query()->find($routeAccount);

        $user = $request->user();

        if ($account === null || $user === null) {
            abort(404);
        }

        $membership = Membership::query()
            ->where('account_id', $account->id)
            ->where('user_id', $user->id)
            ->first();

        if ($membership === null) {
            abort(404);
        }

        $token = $user->currentAccessToken();

        if ($token instanceof ApiToken && $token->account_id !== null && $token->account_id !== $account->id) {
            abort(404);
        }

        app(TenantContext::class)->set($account, $membership->role);

        return $next($request);
    }
}
