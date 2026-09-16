<?php

use App\Providers\AppServiceProvider;
use App\Providers\CommissionServiceProvider;
use App\Providers\PersonalizationServiceProvider;
use App\Providers\StorefrontServiceProvider;

return [
    AppServiceProvider::class,
    PersonalizationServiceProvider::class,
    CommissionServiceProvider::class,
    StorefrontServiceProvider::class,
];
