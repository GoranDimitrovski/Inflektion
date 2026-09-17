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
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\Auth\ResetPasswordController;
use App\Http\Controllers\Auth\TwoFactorAuthenticationController;
use App\Http\Controllers\Auth\TwoFactorChallengeController;
use App\Http\Controllers\Auth\TwoFactorQrCodeController;
use App\Http\Controllers\Auth\TwoFactorRecoveryCodesController;
use App\Http\Controllers\Auth\TwoFactorSecretKeyController;
use App\Http\Controllers\InvitationController;
use App\Http\Controllers\LeaveAccountController;
use App\Http\Controllers\LinkController;
use App\Http\Controllers\MembershipController;
use App\Http\Controllers\ProgramController;
use App\Http\Middleware\ResolveTenant;
use Illuminate\Support\Facades\Route;
use LaravelJsonApi\Laravel\Facades\JsonApiRoute;
use LaravelJsonApi\Laravel\Routing\ResourceRegistrar;

Route::post('register', RegisterController::class)->middleware('throttle:5,1')->name('register');
Route::post('login', LoginController::class)->middleware('throttle:5,1')->name('login');
Route::delete('logout', LogoutController::class)->middleware('auth:sanctum')->name('logout');
Route::post('forgot-password', ForgotPasswordController::class)->middleware('throttle:5,1')->name('password.email');
Route::post('reset-password', ResetPasswordController::class)->middleware('throttle:5,1')->name('password.update');
Route::get('me', MeController::class)->middleware('auth:sanctum')->name('me');

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

Route::post('two-factor-challenge', TwoFactorChallengeController::class)
    ->middleware('throttle:5,1')
    ->name('two-factor.challenge');

Route::get('invitations/{token}', InvitationLookupController::class)
    ->middleware('throttle:20,1')
    ->name('invitations.show');
Route::post('invitations/{token}/accept', AcceptInvitationController::class)
    ->middleware('throttle:5,1')
    ->name('invitations.accept');

JsonApiRoute::server('v1')
    ->prefix('v1/accounts/{account}')
    ->middleware('auth:sanctum', ResolveTenant::class)
    ->resources(function (ResourceRegistrar $server): void {
        $server->resource('programs', ProgramController::class)->only('index', 'store');
        $server->resource('links', LinkController::class)->only('index', 'store', 'update');
        $server->resource('invitations', InvitationController::class)->only('index', 'store', 'destroy');
        $server->resource('memberships', MembershipController::class)->only('index', 'update', 'destroy');
        $server->resource('api-tokens', ApiTokenController::class)->only('index', 'store', 'destroy');
    });

Route::delete('v1/accounts/{account}/me/membership', LeaveAccountController::class)
    ->middleware('auth:sanctum', ResolveTenant::class)
    ->name('accounts.leave');

Route::post('v1/postbacks/{vendor}', PostbackController::class)
    ->name('postbacks.accept')
    ->where('vendor', '[a-z0-9-]+');
