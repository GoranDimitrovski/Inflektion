<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use App\Support\Money;
use App\Support\MoneyCast;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use LogicException;

/**
 * @property int $id
 * @property int $account_id
 * @property int $conversion_id
 * @property int $program_id
 * @property Money $amount
 * @property string $type
 * @property \DateTimeInterface $created_at
 */
final class CommissionLedgerEntry extends Model
{
    use BelongsToTenant;

    public $timestamps = false;

    protected $fillable = [
        'account_id',
        'conversion_id',
        'program_id',
        'amount',
        'type',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'amount' => MoneyCast::class,
            'created_at' => 'datetime',
        ];
    }

    public function update(array $attributes = [], array $options = []): bool
    {
        throw new LogicException('CommissionLedgerEntry is append-only; update() is not allowed.');
    }

    public function delete(): ?bool
    {
        throw new LogicException('CommissionLedgerEntry is append-only; delete() is not allowed.');
    }

    /**
     * @return BelongsTo<Account, $this>
     */
    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    /**
     * @return BelongsTo<Conversion, $this>
     */
    public function conversion(): BelongsTo
    {
        return $this->belongsTo(Conversion::class);
    }

    /**
     * @return BelongsTo<Program, $this>
     */
    public function program(): BelongsTo
    {
        return $this->belongsTo(Program::class);
    }
}
