<?php

declare(strict_types=1);

namespace App\Support\Outbox;

use Illuminate\Database\Eloquent\Model;

final class OutboxMessage extends Model
{
    public $timestamps = false;

    protected $table = 'outbox_messages';

    protected $fillable = [
        'aggregate_type',
        'aggregate_id',
        'event_type',
        'payload',
        'published_at',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'payload' => 'array',
            'published_at' => 'datetime',
            'created_at' => 'datetime',
        ];
    }
}
