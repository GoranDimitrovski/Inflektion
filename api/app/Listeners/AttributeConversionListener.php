<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Actions\Attribution\AttributeConversion;
use App\Events\ConversionRecorded;
use Illuminate\Contracts\Queue\ShouldQueue;

final class AttributeConversionListener implements ShouldQueue
{
    public function __construct(
        private readonly AttributeConversion $attributeConversion,
    ) {}

    public function handle(ConversionRecorded $event): void
    {
        $this->attributeConversion->handle($event->conversion);
    }
}
