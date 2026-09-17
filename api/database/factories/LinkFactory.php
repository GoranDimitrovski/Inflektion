<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Link;
use App\Models\Program;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Link>
 */
final class LinkFactory extends Factory
{
    protected $model = Link::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'program_id' => Program::factory(),
            'account_id' => fn (array $attributes): int => Program::query()->findOrFail($attributes['program_id'])->account_id,
            'destination_url' => fake()->url(),
            'token' => Str::random(10),
            'status' => 'active',
        ];
    }
}
