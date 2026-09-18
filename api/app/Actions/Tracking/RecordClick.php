<?php

declare(strict_types=1);

namespace App\Actions\Tracking;

use App\Models\Click;
use App\Models\Link;
use Illuminate\Support\Facades\DB;

final class RecordClick
{
    public function handle(Link $link, ?string $ipAddress, ?string $userAgent): Click
    {
        return DB::transaction(fn (): Click => Click::create([

            'account_id' => $link->account_id,
            'link_id' => $link->id,
            'ip_address' => $ipAddress,
            'user_agent' => $userAgent,
            'occurred_at' => now(),
        ]));
    }
}
