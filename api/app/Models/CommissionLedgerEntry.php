<?php

declare(strict_types=1);

namespace App\Models;

use App\Support\Money;
use App\Support\MoneyCast;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use LogicException;

/**
 * Append-only: update()/delete() are overridden below to throw. No row in
 * this table may ever be mutated or removed, at any layer, ever.
 *
 * @property int $id
 * @property int $conversion_id
 * @property int $program_id
 * @property Money $amount
 * @property string $type
 * @property \DateTimeInterface $created_at
 */
final class CommissionLedgerEntry extends Model
{
    public $timestamps = false;

    protected $fillable = [
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

    /**
     * @param  array<string, mixed>  $attributes
     * @param  array<string, mixed>  $options
     *
     * @throws LogicException
     */
    public function update(array $attributes = [], array $options = []): bool
    {
        throw new LogicException('CommissionLedgerEntry is append-only; update() is not allowed.');
    }

    /**
     * @throws LogicException
     */
    public function delete(): ?bool
    {
        throw new LogicException('CommissionLedgerEntry is append-only; delete() is not allowed.');
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
