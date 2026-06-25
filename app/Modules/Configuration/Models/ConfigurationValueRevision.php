<?php

declare(strict_types=1);

namespace Modules\Configuration\Models;

use Illuminate\Database\Eloquent\Model;
use LogicException;

final class ConfigurationValueRevision extends Model
{
    public $timestamps = false;

    protected $table = 'configuration_value_revisions';

    protected $fillable = [
        'scope',
        'tenant_id',
        'organization_unit_id',
        'key',
        'action',
        'value_type',
        'is_sensitive',
        'before_exists',
        'before_value',
        'after_exists',
        'after_value',
        'entry_row_version',
        'changed_by',
        'changed_by_name',
        'created_at',
    ];

    protected static function booted(): void
    {
        static::updating(static fn (): never => throw new LogicException(
            'Configuration revisions are immutable.',
        ));
        static::deleting(static fn (): never => throw new LogicException(
            'Configuration revisions cannot be deleted.',
        ));
    }

    protected function casts(): array
    {
        return [
            'tenant_id' => 'integer',
            'organization_unit_id' => 'integer',
            'is_sensitive' => 'boolean',
            'before_exists' => 'boolean',
            'before_value' => 'json',
            'after_exists' => 'boolean',
            'after_value' => 'json',
            'entry_row_version' => 'integer',
            'changed_by' => 'integer',
            'created_at' => 'immutable_datetime',
        ];
    }
}
