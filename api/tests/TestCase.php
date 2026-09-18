<?php

namespace Tests;

use App\Access\Role;
use App\Models\Account;
use App\Models\Membership;
use App\Models\User;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Testing\TestResponse;

abstract class TestCase extends BaseTestCase
{
    protected const JSON_API_MEDIA_TYPE = 'application/vnd.api+json';

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

    /**
     * Without an Origin matching a stateful domain, Sanctum treats the request
     * as token-based and never starts a session — which surfaces as confusing
     * "unauthenticated" or "session store not set" failures.
     *
     * @return array<string, string>
     */
    protected function spaHeaders(): array
    {
        return ['Origin' => 'http://localhost:4200'];
    }

    /**
     * @return array<string, string>
     */
    protected function jsonApiHeaders(): array
    {
        return [
            'CONTENT_TYPE' => self::JSON_API_MEDIA_TYPE,
            'Accept' => self::JSON_API_MEDIA_TYPE,
        ];
    }

    /** The resource name doubles as the JSON:API `type` for every resource in this app. */
    protected function getJsonApi(string $url): TestResponse
    {
        return $this->getJson($url, ['Accept' => self::JSON_API_MEDIA_TYPE]);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    protected function postJsonApi(Account $account, string $resource, array $attributes): TestResponse
    {
        return $this->postJson("/api/v1/accounts/{$account->id}/{$resource}", [
            'data' => [
                'type' => $resource,
                'attributes' => $attributes,
            ],
        ], $this->jsonApiHeaders());
    }
}
