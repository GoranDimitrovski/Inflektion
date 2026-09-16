<?php

declare(strict_types=1);

namespace App\Personalization;

use App\Models\Link;
use App\Personalization\Strategies\NullVariantStrategy;
use Illuminate\Support\Facades\Log;
use Throwable;

final class PersonalizationStrategyRegistry
{
    /**
     * @param  array<string, class-string<PersonalizationStrategy>>  $strategies
     */
    public function __construct(
        private readonly NullVariantStrategy $nullStrategy,
        private readonly array $strategies,
    ) {}

    public function decide(?string $identifier, Link $link, PersonalizationContext $context): Variant
    {
        if ($identifier === null || ! isset($this->strategies[$identifier])) {
            return $this->nullStrategy->decide($link, $context);
        }

        try {
            /** @var PersonalizationStrategy $strategy */
            $strategy = app($this->strategies[$identifier]);

            return $strategy->decide($link, $context);
        } catch (Throwable $e) {
            // Personalization must never break the redirect — a broken rule
            // silently degrades to the null variant instead of surfacing.
            Log::warning('Personalization strategy failed, falling back to null variant', [
                'strategy' => $identifier,
                'exception' => $e::class,
            ]);

            return $this->nullStrategy->decide($link, $context);
        }
    }
}
