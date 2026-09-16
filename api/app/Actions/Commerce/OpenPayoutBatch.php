<?php

declare(strict_types=1);

namespace App\Actions\Commerce;

use App\Models\PayoutBatch;
use App\Support\Clock;
use Illuminate\Support\Facades\DB;

final class OpenPayoutBatch
{
    public function __construct(
        private readonly Clock $clock,
    ) {}

    public function handle(): PayoutBatch
    {
        return DB::transaction(fn (): PayoutBatch => PayoutBatch::create([
            'status' => PayoutBatch::STATUS_OPEN,
            'opened_at' => $this->clock->now(),
        ]));
    }
}
