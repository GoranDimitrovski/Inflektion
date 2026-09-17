<?php

declare(strict_types=1);

namespace App\Access;

use DateTimeInterface;
use Illuminate\Database\Eloquent\Model;
use LogicException;

final class AuditEntry extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'account_id',
        'actor_user_id',
        'action',
        'subject_type',
        'subject_id',
        'metadata',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'metadata' => 'array',
            'created_at' => 'datetime',
        ];
    }

    /**
     * @param  array<string, mixed>  $metadata
     */
    public static function record(
        ?int $accountId,
        ?int $actorUserId,
        string $action,
        ?Model $subject,
        array $metadata,
        DateTimeInterface $at,
    ): self {
        return self::create([
            'account_id' => $accountId,
            'actor_user_id' => $actorUserId,
            'action' => $action,
            'subject_type' => $subject?->getMorphClass(),
            'subject_id' => $subject?->getKey(),
            'metadata' => $metadata,
            'created_at' => $at,
        ]);
    }

    public function update(array $attributes = [], array $options = []): bool
    {
        throw new LogicException('AuditEntry is append-only; update() is not allowed.');
    }

    public function delete(): ?bool
    {
        throw new LogicException('AuditEntry is append-only; delete() is not allowed.');
    }
}
