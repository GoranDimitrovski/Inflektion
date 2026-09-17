<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Carbon;
use LogicException;

/**
 * @property int $id
 * @property int $account_id
 * @property string $status
 * @property Carbon $opened_at
 * @property Carbon|null $closed_at
 */
final class PayoutBatch extends Model
{
    use BelongsToTenant;

    public const STATUS_OPEN = 'open';

    public const STATUS_CLOSED = 'closed';

    protected $fillable = [
        'account_id',
        'status',
        'opened_at',
        'closed_at',
    ];

    protected function casts(): array
    {
        return [
            'opened_at' => 'datetime',
            'closed_at' => 'datetime',
        ];
    }

    public function close(\DateTimeInterface $closedAt): void
    {
        if ($this->status !== self::STATUS_OPEN) {
            throw new LogicException("Cannot close payout batch [{$this->id}]: it is not open (current status [{$this->status}]).");
        }

        $this->update([
            'status' => self::STATUS_CLOSED,
            'closed_at' => $closedAt,
        ]);
    }

    public function isOpen(): bool
    {
        return $this->status === self::STATUS_OPEN;
    }

    /**
     * @return BelongsTo<Account, $this>
     */
    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    /**
     * @return BelongsToMany<CommissionLedgerEntry, $this>
     */
    public function commissionLedgerEntries(): BelongsToMany
    {
        return $this->belongsToMany(
            CommissionLedgerEntry::class,
            'payout_batch_entries',
        )->withPivot('created_at');
    }
}
