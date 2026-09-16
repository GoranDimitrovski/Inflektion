<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Actions\Commerce\WriteCommission;
use App\Events\ConversionAttributed;
use Illuminate\Contracts\Queue\ShouldQueue;

final class WriteCommissionListener implements ShouldQueue
{
    public function __construct(
        private readonly WriteCommission $writeCommission,
    ) {}

    public function handle(ConversionAttributed $event): void
    {
        $this->writeCommission->handle($event->conversion);
    }
}
