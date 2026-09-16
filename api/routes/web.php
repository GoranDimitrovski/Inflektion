<?php

use App\Http\Controllers\Tracking\RedirectController;
use Illuminate\Support\Facades\Route;

// This is an API-only service (see routes/api.php) — Angular in web/ is the
// frontend. Nothing here is meant to render HTML for end users.
Route::get('/', fn () => response()->json(['name' => config('app.name'), 'status' => 'ok']));

// Affiliate click/redirect endpoint. Lives outside /api because it's hit
// directly by browsers following an affiliate link, not called by the
// Angular app — a 302 redirect isn't a JSON:API document response either.
Route::get('/r/{token}', RedirectController::class)->name('tracking.redirect');
