<?php

namespace Tests;

use App\Access\Role;
use App\Models\Account;
use App\Models\Membership;
use App\Models\User;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function actingAsAccountMember(?Account $account = null, Role $role = Role::Member): Account
    {
        $account ??= Account::factory()->create();

        $user = User::factory()->create();

        Membership::factory()->create([
            'account_id' => $account->id,
            'user_id' => $user->id,
            'role' => $role,
        ]);

        $this->actingAs($user);

        return $account;
    }
}
