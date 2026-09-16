<?php

declare(strict_types=1);

namespace App\Personalization\Strategies;

use App\Models\Link;
use App\Personalization\PersonalizationContext;
use App\Personalization\PersonalizationStrategy;
use App\Personalization\Variant;

final class WeightedVariantStrategy implements PersonalizationStrategy
{
    /**
     * @var array<string, int>
     */
    private const array WEIGHTS = [
        'control' => 70,
        'variant-a' => 20,
        'variant-b' => 10,
    ];

    public function decide(Link $link, PersonalizationContext $context): Variant
    {
        $roll = random_int(1, 100);
        $cumulative = 0;

        foreach (self::WEIGHTS as $key => $weight) {
            $cumulative += $weight;

            if ($roll <= $cumulative) {
                return new Variant($key, ['variant' => $key]);
            }
        }

        return Variant::none();
    }
}
