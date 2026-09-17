<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Account;
use App\Models\Program;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Program>
 */
final class ProgramFactory extends Factory
{
    protected $model = Program::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->unique()->company();

        return [
            'account_id' => Account::factory(),
            'name' => $name,
            'slug' => Str::slug($name).'-'.fake()->unique()->numberBetween(1, 999999),
            'status' => 'draft',
        ];
    }
}
