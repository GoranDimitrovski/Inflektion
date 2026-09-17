<?php

declare(strict_types=1);

namespace App\Models;

use App\Access\Role;
use Database\Factories\MembershipFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $account_id
 * @property int $user_id
 * @property Role $role
 */
final class Membership extends Model
{
    /** @use HasFactory<MembershipFactory> */
    use HasFactory;

    protected $fillable = [
        'account_id',
        'user_id',
        'role',
    ];

    protected function casts(): array
    {
        return [
            'role' => Role::class,
        ];
    }

    /**
     * @return BelongsTo<Account, $this>
     */
    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isTheLastOwner(): bool
    {
        if ($this->role !== Role::Owner) {
            return false;
        }

        return ! self::query()
            ->where('account_id', $this->account_id)
            ->where('role', Role::Owner)
            ->whereKeyNot($this->id)
            ->exists();
    }
}
