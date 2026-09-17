<?php

use App\Http\Controllers\ApiTokenController;
use App\Http\Controllers\Attribution\PostbackController;
use App\Http\Controllers\Auth\AcceptInvitationController;
use App\Http\Controllers\Auth\ConfirmedTwoFactorAuthenticationController;
use App\Http\Controllers\Auth\ForgotPasswordController;
use App\Http\Controllers\Auth\InvitationLookupController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\LogoutController;
use App\Http\Controllers\Auth\MeController;
use App\Http\Controllers\Auth\ResetPasswordController;
use App\Http\Controllers\Auth\TwoFactorAuthenticationController;
use App\Http\Controllers\Auth\TwoFactorChallengeController;
use App\Http\Controllers\Auth\TwoFactorQrCodeController;
use App\Http\Controllers\Auth\TwoFactorRecoveryCodesController;
use App\Http\Controllers\Auth\TwoFactorSecretKeyController;
use App\Http\Controllers\InvitationController;
use App\Http\Controllers\LeaveAccountController;
use App\Http\Controllers\MembershipController;
use App\Http\Controllers\ProgramController;
use App\Http\Middleware\ResolveTenant;
use Illuminate\Support\Facades\Route;
use LaravelJsonApi\Laravel\Facades\JsonApiRoute;
use LaravelJsonApi\Laravel\Routing\ResourceRegistrar;

Route::post('login', LoginController::class)->middleware('throttle:5,1')->name('login');
Route::delete('logout', LogoutController::class)->middleware('auth:sanctum')->name('logout');
Route::post('forgot-password', ForgotPasswordController::class)->middleware('throttle:5,1')->name('password.email');
Route::post('reset-password', ResetPasswordController::class)->middleware('throttle:5,1')->name('password.update');
Route::get('me', MeController::class)->middleware('auth:sanctum')->name('me');

// 2FA is a per-user setting, not account-scoped, so none of this sits
// under /v1/accounts/{account}/... or goes through ResolveTenant.
Route::post('two-factor-authentication', [TwoFactorAuthenticationController::class, 'store'])
    ->middleware('auth:sanctum')
    ->name('two-factor.enable');
Route::delete('two-factor-authentication', [TwoFactorAuthenticationController::class, 'destroy'])
    ->middleware('auth:sanctum')
    ->name('two-factor.disable');
Route::post('confirmed-two-factor-authentication', [ConfirmedTwoFactorAuthenticationController::class, 'store'])
    ->middleware('auth:sanctum')
    ->name('two-factor.confirm');
Route::get('two-factor-qr-code', [TwoFactorQrCodeController::class, 'show'])
    ->middleware('auth:sanctum')
    ->name('two-factor.qr-code');
Route::get('two-factor-secret-key', [TwoFactorSecretKeyController::class, 'show'])
    ->middleware('auth:sanctum')
    ->name('two-factor.secret-key');
Route::get('two-factor-recovery-codes', [TwoFactorRecoveryCodesController::class, 'index'])
    ->middleware('auth:sanctum')
    ->name('two-factor.recovery-codes.index');
Route::post('two-factor-recovery-codes', [TwoFactorRecoveryCodesController::class, 'store'])
    ->middleware('auth:sanctum')
    ->name('two-factor.recovery-codes.store');

// No auth:sanctum — the caller has no session yet at this point, only the
// pending "login.id" LoginController put in it.
Route::post('two-factor-challenge', TwoFactorChallengeController::class)
    ->middleware('throttle:5,1')
    ->name('two-factor.challenge');

// Public: identified by the invitation's own token rather than tenant
// membership, so these sit outside ResolveTenant entirely. The accept
// endpoint is reachable both as a guest (registers or is told to log in
// first) and while authenticated (AcceptInvitation itself guards against
// accepting as the wrong signed-in user).
Route::get('invitations/{token}', InvitationLookupController::class)
    ->middleware('throttle:20,1')
    ->name('invitations.show');
Route::post('invitations/{token}/accept', AcceptInvitationController::class)
    ->middleware('throttle:5,1')
    ->name('invitations.accept');

// Every resource below belongs to an account: the account travels in the
// URL (not a header or a session value), ResolveTenant asserts membership
// and fills TenantContext, and each model's own global scope does the rest.
JsonApiRoute::server('v1')
    ->prefix('v1/accounts/{account}')
    ->middleware('auth:sanctum', ResolveTenant::class)
    ->resources(function (ResourceRegistrar $server): void {
        $server->resource('programs', ProgramController::class)->only('index', 'store');
        $server->resource('invitations', InvitationController::class)->only('index', 'store', 'destroy');
        $server->resource('memberships', MembershipController::class)->only('index', 'update', 'destroy');
        $server->resource('api-tokens', ApiTokenController::class)->only('index', 'store', 'destroy');
    });

// Self-leave is not a JSON:API resource action (it's "delete my own
// membership", not a generic member-management operation), so it's a plain
// route under the same tenant-scoped middleware.
Route::delete('v1/accounts/{account}/me/membership', LeaveAccountController::class)
    ->middleware('auth:sanctum', ResolveTenant::class)
    ->name('accounts.leave');

// Inbound storefront postbacks — not a JSON:API resource, so registered as a
// plain route rather than through JsonApiRoute.
Route::post('v1/postbacks/{vendor}', PostbackController::class)
    ->name('postbacks.accept')
    ->where('vendor', '[a-z0-9-]+');
