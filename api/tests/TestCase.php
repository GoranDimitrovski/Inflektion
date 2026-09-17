<?php

namespace Tests;

use App\Access\Role;
use App\Models\Account;
use App\Models\Membership;
use App\Models\User;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    /**
     * Creates and authenticates a user with a Membership on a (new, by
     * default) account, and returns that account so tests can build
     * account-scoped URLs and seed account-owned rows.
     */
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
