<?php

declare(strict_types=1);

namespace App\Providers;

use App\Integrations\Storefronts\DemoStore\DemoStoreAdapter;
use App\Integrations\Storefronts\StorefrontAdapterRegistry;
use Illuminate\Support\ServiceProvider;

final class StorefrontServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(
            StorefrontAdapterRegistry::class,
            fn (): StorefrontAdapterRegistry => new StorefrontAdapterRegistry([
                'demo-store' => DemoStoreAdapter::class,
            ]),
        );
    }
}
