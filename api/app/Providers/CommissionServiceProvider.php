<?php

declare(strict_types=1);

namespace App\Providers;

use App\Commissions\CommissionStrategyRegistry;
use App\Commissions\Strategies\FlatCommissionStrategy;
use App\Commissions\Strategies\PercentageCommissionStrategy;
use Illuminate\Support\ServiceProvider;

final class CommissionServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(
            CommissionStrategyRegistry::class,
            fn (): CommissionStrategyRegistry => new CommissionStrategyRegistry([
                'percentage' => PercentageCommissionStrategy::class,
                'flat' => FlatCommissionStrategy::class,
            ]),
        );
    }
}
