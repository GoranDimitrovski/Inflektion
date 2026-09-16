<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// First real caller of the outbox worker (commission/payout messages, Phase 6):
// run it frequently enough that nothing sits unpublished for long.
Schedule::command('outbox:publish')->everyMinute()->withoutOverlapping();
