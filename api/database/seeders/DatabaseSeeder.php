<?php

namespace Database\Seeders;

use App\Access\Role;
use App\Models\Account;
use App\Models\Membership;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        $user = User::query()->updateOrCreate(
            ['email' => 'owner@example.test'],
            ['name' => 'Owner', 'password' => Hash::make('password')],
        );

        $account = Account::query()->firstOrCreate(
            ['slug' => 'example-co'],
            ['name' => 'Example Co'],
        );

        Membership::query()->updateOrCreate(
            ['account_id' => $account->id, 'user_id' => $user->id],
            ['role' => Role::Owner],
        );
    }
}
