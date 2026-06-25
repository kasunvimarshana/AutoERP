<?php

declare(strict_types=1);

namespace Modules\Configuration\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use LogicException;
use Modules\Core\Models\CoreModel;
use Modules\User\Models\UserModel;

final class ConfigurationValueRevision extends CoreModel
{
    public $timestamps = false;

    protected $table = 'configuration_value_revisions';

    protected $fillable = [
        'scope', 'tenant_id', 'organization_unit_id', 'key', 'operation', 'stored_value',
        'value_type', 'is_sensitive', 'resulting_row_version', 'source_revision_id',
        'actor_user_id', 'reason', 'created_at',
    ];

    protected static function booted(): void
    {
        static::updating(static fn (): never => throw new LogicException('Configuration revisions are immutable.'));
        static::deleting(static fn (): never => throw new LogicException('Configuration revisions cannot be deleted.'));
    }

    protected function casts(): array
    {
        return [
            'tenant_id' => 'integer',
            'organization_unit_id' => 'integer',
            'resulting_row_version' => 'integer',
            'source_revision_id' => 'integer',
            'actor_user_id' => 'integer',
            'is_sensitive' => 'boolean',
            'created_at' => 'immutable_datetime',
        ];
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(UserModel::class, 'actor_user_id');
    }

    public function sourceRevision(): BelongsTo
    {
        return $this->belongsTo(self::class, 'source_revision_id');
    }
}
