<?php

use App\Providers\AccessServiceProvider;
use App\Providers\AppServiceProvider;
use App\Providers\CommissionServiceProvider;
use App\Providers\PersonalizationServiceProvider;
use App\Providers\StorefrontServiceProvider;

return [
    AppServiceProvider::class,
    AccessServiceProvider::class,
    PersonalizationServiceProvider::class,
    CommissionServiceProvider::class,
    StorefrontServiceProvider::class,
];
