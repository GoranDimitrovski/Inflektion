<?php

declare(strict_types=1);

namespace App\Actions\Access;

use App\Access\AuditEntry;
use App\Access\PermissionMap;
use App\Access\Role;
use App\Models\Account;
use App\Models\User;
use DateTimeInterface;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\NewAccessToken;
use LogicException;

final class IssueApiToken
{
    public function __construct(
        private readonly PermissionMap $permissions,
    ) {}

    /**
     * @param  list<string>  $abilities
     *
     * @throws LogicException
     */
    public function handle(
        Account $account,
        User $actor,
        Role $actorRole,
        string $name,
        array $abilities,
        ?DateTimeInterface $expiresAt,
    ): NewAccessToken {
        $allowedAbilities = $this->permissions->permissionsFor($actorRole);

        foreach ($abilities as $ability) {
            if (! in_array($ability, $allowedAbilities, true)) {
                throw new LogicException("A token cannot be granted the [{$ability}] ability, which your own role does not have.");
            }
        }

        return DB::transaction(function () use ($account, $actor, $name, $abilities, $expiresAt): NewAccessToken {
            $newAccessToken = $actor->createToken($name, $abilities, $expiresAt);
            $newAccessToken->accessToken->update(['account_id' => $account->id]);

            AuditEntry::record($account->id, $actor->id, 'access.token_issued', $newAccessToken->accessToken, [
                'name' => $name,
                'abilities' => $abilities,
            ], now());

            return $newAccessToken;
        });
    }
}
