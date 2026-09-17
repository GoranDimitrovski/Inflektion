<?php

declare(strict_types=1);

namespace App\Actions\Access;

use App\Access\AuditEntry;
use App\Access\Role;
use App\Models\Account;
use App\Models\Membership;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

final class RegisterAccount
{
    public function handle(string $name, string $email, string $password, string $accountName): User
    {
        return DB::transaction(function () use ($name, $email, $password, $accountName): User {
            $user = User::create([
                'name' => $name,
                'email' => $email,
                'password' => Hash::make($password),
            ]);

            $account = Account::create([
                'name' => $accountName,
                'slug' => Str::slug($accountName).'-'.Str::lower(Str::random(6)),
            ]);

            Membership::create([
                'account_id' => $account->id,
                'user_id' => $user->id,
                'role' => Role::Owner,
            ]);

            AuditEntry::record($account->id, $user->id, 'access.account_registered', $account, [], now());

            return $user;
        });
    }
}
