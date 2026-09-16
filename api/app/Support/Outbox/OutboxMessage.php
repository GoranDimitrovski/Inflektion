<?php

declare(strict_types=1);

namespace App\Support\Outbox;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property string $aggregate_type
 * @property string $aggregate_id
 * @property string $event_type
 * @property array<string, mixed> $payload
 * @property \DateTimeInterface|null $published_at
 */
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
