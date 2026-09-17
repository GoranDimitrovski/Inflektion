<?php

declare(strict_types=1);

use Laravel\Fortify\Features;

/*
 * Only the 2FA feature is enabled — registration, password update, and
 * profile-information features stay off since this app already has its own
 * hand-rolled equivalents (or, for profile/registration, doesn't need them
 * yet). Every other key (guard, passwords broker, username field, passkeys
 * defaults, ...) inherits Laravel Fortify's own package defaults via
 * mergeConfigFrom() — this file only overrides what actually differs.
 */
return [
    'features' => [
        Features::twoFactorAuthentication(['confirm' => true]),
    ],
];
