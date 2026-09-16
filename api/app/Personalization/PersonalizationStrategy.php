<?php

declare(strict_types=1);

namespace App\Personalization;

use App\Models\Link;

interface PersonalizationStrategy
{
    public function decide(Link $link, PersonalizationContext $context): Variant;
}
