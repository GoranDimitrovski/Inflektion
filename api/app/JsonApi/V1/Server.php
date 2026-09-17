<?php

declare(strict_types=1);

namespace App\JsonApi\V1;

use App\JsonApi\V1\ApiTokens\ApiTokenSchema;
use App\JsonApi\V1\Invitations\InvitationSchema;
use App\JsonApi\V1\Links\LinkSchema;
use App\JsonApi\V1\Memberships\MembershipSchema;
use App\JsonApi\V1\Programs\ProgramSchema;
use App\Models\Account;
use LaravelJsonApi\Core\Server\Server as BaseServer;

class Server extends BaseServer
{
    public function serving(): void {}

    protected function baseUri(): string
    {
        $account = request()->route('account');
        $accountId = $account instanceof Account ? $account->getRouteKey() : $account;

        return "/api/v1/accounts/{$accountId}";
    }

    /**
     * @return array<int, class-string>
     */
    protected function allSchemas(): array
    {
        return [
            ProgramSchema::class,
            LinkSchema::class,
            InvitationSchema::class,
            MembershipSchema::class,
            ApiTokenSchema::class,
        ];
    }
}
