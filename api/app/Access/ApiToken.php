<?php

declare(strict_types=1);

namespace App\Access;

use App\Models\Account;
use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Auth;
use Laravel\Sanctum\PersonalAccessToken;

final class ApiToken extends PersonalAccessToken
{
    use BelongsToTenant;

    protected $table = 'personal_access_tokens';

    protected $fillable = [
        'name',
        'token',
        'abilities',
        'expires_at',
        'account_id',
        'tokenable_type',
        'tokenable_id',
    ];

    protected static function booted(): void
    {
        self::addGlobalScope('own', function (Builder $builder): void {
            $user = Auth::user();

            if ($user !== null) {
                $builder
                    ->where('tokenable_type', $user->getMorphClass())
                    ->where('tokenable_id', $user->getKey());
            }
        });
    }

    /**
     * @return BelongsTo<Account, $this>
     */
    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }
}
