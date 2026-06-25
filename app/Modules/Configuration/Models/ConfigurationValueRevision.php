<?php

declare(strict_types=1);

namespace Modules\Configuration\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use InvalidArgumentException;
use LogicException;
use Modules\Configuration\Constants\ConfigurationActorType;
use Modules\Core\Models\CoreModel;

abstract class ConfigurationValueRevision extends CoreModel
{
    public $timestamps = false;

    protected $fillable = [
        'tenant_id',
        'organization_unit_id',
        'key',
        'definition_version',
        'operation',
        'stored_value',
        'value_type',
        'is_sensitive',
        'resulting_row_version',
        'source_revision_id',
        'actor_type',
        'actor_id',
        'actor_name',
        'actor_email',
        'reason',
        'created_at',
    ];

    protected static function booted(): void
    {
        static::creating(static function (self $revision): void {
            $actorType = (string) $revision->getAttribute('actor_type');
            $actorId = $revision->getAttribute('actor_id');

            if (! in_array($actorType, ConfigurationActorType::values(), true)) {
                throw new InvalidArgumentException('Configuration revision actor type is invalid.');
            }
            if ($actorType === ConfigurationActorType::SYSTEM && $actorId !== null) {
                throw new InvalidArgumentException('System configuration revisions cannot reference a user actor.');
            }
            if ($actorType !== ConfigurationActorType::SYSTEM
                && (! is_numeric($actorId) || (int) $actorId < 1)
            ) {
                throw new InvalidArgumentException('User configuration revisions require a valid actor identifier.');
            }
        });
        static::updating(static fn (): never => throw new LogicException('Configuration revisions are immutable.'));
        static::deleting(static fn (): never => throw new LogicException('Configuration revisions cannot be deleted.'));
    }

    protected function casts(): array
    {
        return [
            'tenant_id' => 'integer',
            'organization_unit_id' => 'integer',
            'definition_version' => 'integer',
            'resulting_row_version' => 'integer',
            'source_revision_id' => 'integer',
            'actor_id' => 'integer',
            'is_sensitive' => 'boolean',
            'created_at' => 'immutable_datetime',
        ];
    }

    abstract public function scopeName(): string;

    public function tenantId(): ?int
    {
        $value = $this->getAttribute('tenant_id');

        return is_numeric($value) ? (int) $value : null;
    }

    public function organizationUnitId(): ?int
    {
        $value = $this->getAttribute('organization_unit_id');

        return is_numeric($value) ? (int) $value : null;
    }

    public function sourceRevision(): BelongsTo
    {
        return $this->belongsTo(static::class, 'source_revision_id');
    }
}
