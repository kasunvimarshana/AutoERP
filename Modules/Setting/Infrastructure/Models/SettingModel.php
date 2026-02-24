<?php
namespace Modules\Setting\Infrastructure\Models;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Modules\Shared\Infrastructure\Traits\HasTenantScope;
class SettingModel extends Model
{
    use HasUuids, HasTenantScope;
    protected $table = 'settings';
    protected $fillable = ['id', 'tenant_id', 'group', 'key', 'value', 'type', 'validation_rules', 'is_global', 'version'];
    protected $casts = ['validation_rules' => 'array', 'is_global' => 'boolean', 'version' => 'integer'];
}
