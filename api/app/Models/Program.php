<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use App\Support\Money;
use App\Support\MoneyCast;
use Database\Factories\ProgramFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $account_id
 * @property string $name
 * @property string $slug
 * @property string $status
 * @property string|null $commission_strategy
 * @property string|null $commission_rate
 * @property Money|null $commission_flat_amount
 */
final class Program extends Model
{
    /** @use HasFactory<ProgramFactory> */
    use BelongsToTenant, HasFactory;

    protected $fillable = [
        'account_id',
        'name',
        'slug',
        'status',
        'commission_strategy',
        'commission_rate',
        'commission_flat_amount',
    ];

    protected function casts(): array
    {
        return [
            'commission_flat_amount' => MoneyCast::class,
        ];
    }

    /**
     * @return BelongsTo<Account, $this>
     */
    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }
}
