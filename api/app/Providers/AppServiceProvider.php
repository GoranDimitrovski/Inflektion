<?php

namespace App\Providers;

use App\Support\Clock;
use App\Support\SystemClock;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Contracts\Auth\CanResetPassword;
use Illuminate\Support\ServiceProvider;
use Laravel\Fortify\Fortify;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(Clock::class, SystemClock::class);
    }

    public function boot(): void
    {

        ResetPassword::createUrlUsing(function (CanResetPassword $notifiable, string $token): string {
            $frontendUrl = rtrim((string) config('services.frontend_url'), '/');

            return "{$frontendUrl}/reset-password?token={$token}&email=".urlencode($notifiable->getEmailForPasswordReset());
        });

        Fortify::ignoreRoutes();
    }
}
