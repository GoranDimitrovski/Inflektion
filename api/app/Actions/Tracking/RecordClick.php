<?php

declare(strict_types=1);

namespace App\Actions\Tracking;

use App\Models\Click;
use App\Models\Link;
use App\Support\Clock;
use Illuminate\Support\Facades\DB;

final class RecordClick
{
    public function __construct(
        private readonly Clock $clock,
    ) {}

    public function handle(Link $link, ?string $ipAddress, ?string $userAgent): Click
    {
        return DB::transaction(fn (): Click => Click::create([
            'link_id' => $link->id,
            'ip_address' => $ipAddress,
            'user_agent' => $userAgent,
            'occurred_at' => $this->clock->now(),
        ]));
    }
}
