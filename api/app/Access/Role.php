<?php

declare(strict_types=1);

namespace App\Access;

enum Role: string
{
    case Owner = 'owner';
    case Admin = 'admin';
    case Member = 'member';
    case Viewer = 'viewer';

    public function rank(): int
    {
        return [
            self::Owner->value => 4,
            self::Admin->value => 3,
            self::Member->value => 2,
            self::Viewer->value => 1,
        ][$this->value];
    }
}
