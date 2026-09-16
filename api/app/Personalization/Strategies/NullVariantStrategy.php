<?php

declare(strict_types=1);

namespace App\Personalization\Strategies;

use App\Models\Link;
use App\Personalization\PersonalizationContext;
use App\Personalization\PersonalizationStrategy;
use App\Personalization\Variant;

final class NullVariantStrategy implements PersonalizationStrategy
{
    public function decide(Link $link, PersonalizationContext $context): Variant
    {
        return Variant::none();
    }
}
