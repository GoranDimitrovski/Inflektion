<?php

return [

    'demo_store' => [
        // HMAC secret DemoStore signs postbacks with. A per-environment
        // value in production; a fixed default here so local/test postbacks
        // can be signed deterministically (see the DemoStore fixture).
        'secret' => env('DEMO_STORE_SECRET', 'demo-store-shared-secret'),
    ],

    // The Angular app's origin. Used to build links that point at the SPA
    // rather than a Laravel route — e.g. the password reset email — since
    // this app has no server-rendered views of its own.
    'frontend_url' => env('FRONTEND_URL', 'http://localhost:4200'),

];
