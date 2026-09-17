<?php

use App\Http\Controllers\Tracking\RedirectController;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => response()->json(['name' => config('app.name'), 'status' => 'ok']));

Route::get('/r/{token}', RedirectController::class)->name('tracking.redirect');
