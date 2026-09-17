<?php

declare(strict_types=1);

namespace App\Models;

use App\Access\Role;
use App\Models\Concerns\BelongsToTenant;
use Database\Factories\InvitationFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use LogicException;

/**
 * @property int $id
 * @property int $account_id
 * @property string $email
 * @property Role $role
 * @property int $invited_by_user_id
 * @property string $token
 * @property string $status
 * @property Carbon $expires_at
 * @property Carbon|null $accepted_at
 */
final class Invitation extends Model
{
    /** @use HasFactory<InvitationFactory> */
    use BelongsToTenant, HasFactory;

    public const STATUS_PENDING = 'pending';

    public const STATUS_ACCEPTED = 'accepted';

    public const STATUS_REVOKED = 'revoked';

    protected $fillable = [
        'account_id',
        'email',
        'role',
        'invited_by_user_id',
        'token',
        'status',
        'expires_at',
        'accepted_at',
    ];

    protected function casts(): array
    {
        return [
            'role' => Role::class,
            'expires_at' => 'datetime',
            'accepted_at' => 'datetime',
        ];
    }

    /**
     * @throws LogicException
     */
    public function accept(\DateTimeInterface $at): void
    {
        if (! $this->isPending()) {
            throw new LogicException("Cannot accept invitation [{$this->id}]: it is not pending (current status [{$this->status}]).");
        }

        $this->update([
            'status' => self::STATUS_ACCEPTED,
            'accepted_at' => $at,
        ]);
    }

    /**
     * @throws LogicException
     */
    public function revoke(): void
    {
        if (! $this->isPending()) {
            throw new LogicException("Cannot revoke invitation [{$this->id}]: it is not pending (current status [{$this->status}]).");
        }

        $this->update(['status' => self::STATUS_REVOKED]);
    }

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING && $this->expires_at->isFuture();
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
    public function invitedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'invited_by_user_id');
    }
}
