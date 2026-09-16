<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use LogicException;

/**
 * Explicit state machine: open -> closed. State transitions are methods on
 * the model, not `if`s at call sites (see project "where logic goes" rule).
 *
 * @property int $id
 * @property string $status
 * @property \DateTimeInterface $opened_at
 * @property \DateTimeInterface|null $closed_at
 */
final class PayoutBatch extends Model
{
    public const STATUS_OPEN = 'open';

    public const STATUS_CLOSED = 'closed';

    protected $fillable = [
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

    /**
     * @throws LogicException
     */
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
