<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $account_id
 * @property int $link_id
 * @property string|null $ip_address
 * @property string|null $user_agent
 * @property Carbon $occurred_at
 */
final class Click extends Model
{
    use BelongsToTenant;

    public $timestamps = false;

    protected $fillable = [
        'account_id',
        'link_id',
        'ip_address',
        'user_agent',
        'occurred_at',
    ];

    protected function casts(): array
    {
        return [
            'occurred_at' => 'datetime',
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
     * @return BelongsTo<Link, $this>
     */
    public function link(): BelongsTo
    {
        return $this->belongsTo(Link::class);
    }
}
