<?php

use App\Http\Controllers\Attribution\PostbackController;
use App\Http\Controllers\ProgramController;
use Illuminate\Support\Facades\Route;
use LaravelJsonApi\Laravel\Facades\JsonApiRoute;
use LaravelJsonApi\Laravel\Routing\ResourceRegistrar;

JsonApiRoute::server('v1')->prefix('v1')->resources(function (ResourceRegistrar $server): void {
    $server->resource('programs', ProgramController::class)->only('index', 'store');
});

// Inbound storefront postbacks — not a JSON:API resource, so registered as a
// plain route rather than through JsonApiRoute.
Route::post('v1/postbacks/{vendor}', PostbackController::class)
    ->name('postbacks.accept')
    ->where('vendor', '[a-z0-9-]+');
