<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Access\Role;
use App\Models\Account;
use App\Models\Invitation;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Invitation>
 */
final class InvitationFactory extends Factory
{
    protected $model = Invitation::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'account_id' => Account::factory(),
            'email' => fake()->unique()->safeEmail(),
            'role' => Role::Member,
            'invited_by_user_id' => User::factory(),
            'token' => hash('sha256', Str::random(40)),
            'status' => Invitation::STATUS_PENDING,
            'expires_at' => now()->addDays(7),
        ];
    }
}
