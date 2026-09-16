<?php

declare(strict_types=1);

namespace App\Providers;

use App\Personalization\PersonalizationStrategyRegistry;
use App\Personalization\Strategies\NullVariantStrategy;
use App\Personalization\Strategies\WeightedVariantStrategy;
use Illuminate\Support\ServiceProvider;

final class PersonalizationServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(
            PersonalizationStrategyRegistry::class,
            fn (): PersonalizationStrategyRegistry => new PersonalizationStrategyRegistry(
                new NullVariantStrategy,
                ['weighted' => WeightedVariantStrategy::class],
            ),
        );
    }
}
