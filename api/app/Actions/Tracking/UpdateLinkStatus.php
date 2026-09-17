<?php

declare(strict_types=1);

namespace App\Actions\Tracking;

use App\Access\AuditEntry;
use App\Models\Link;
use App\Models\User;
use Illuminate\Support\Facades\DB;

final class UpdateLinkStatus
{
    public function handle(Link $link, string $status, User $actor): Link
    {
        return DB::transaction(function () use ($link, $status, $actor): Link {
            $link->update(['status' => $status]);

            AuditEntry::record($link->account_id, $actor->id, 'tracking.link_status_changed', $link, ['status' => $status], now());

            return $link;
        });
    }
}
