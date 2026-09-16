<?php

declare(strict_types=1);

namespace App\Events;

use App\Models\Conversion;

final class ConversionAttributed
{
    public function __construct(
        public readonly Conversion $conversion,
    ) {}
}
