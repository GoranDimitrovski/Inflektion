<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use App\Support\Money;
use App\Support\MoneyCast;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int|null $account_id
 * @property string $vendor
 * @property string $external_id
 * @property int|null $click_id
 * @property Money $amount
 * @property string $status
 * @property int|null $attributed_program_id
 */
final class Conversion extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'account_id',
        'vendor',
        'external_id',
        'click_id',
        'amount',
        'status',
        'attributed_program_id',
    ];

    protected function casts(): array
    {
        return [
            'amount' => MoneyCast::class,
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
     * @return BelongsTo<Click, $this>
     */
    public function click(): BelongsTo
    {
        return $this->belongsTo(Click::class);
    }

    /**
     * @return BelongsTo<Program, $this>
     */
    public function attributedProgram(): BelongsTo
    {
        return $this->belongsTo(Program::class, 'attributed_program_id');
    }
}
