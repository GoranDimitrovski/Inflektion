<?php

declare(strict_types=1);

namespace App\Providers;

use App\Access\ApiToken;
use App\Access\TenantContext;
use Illuminate\Support\ServiceProvider;
use Laravel\Sanctum\Sanctum;

final class AccessServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->scoped(TenantContext::class);
    }

    public function boot(): void
    {
        Sanctum::usePersonalAccessTokenModel(ApiToken::class);
    }
}
