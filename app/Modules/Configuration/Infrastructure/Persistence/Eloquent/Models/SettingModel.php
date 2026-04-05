<?php
declare(strict_types=1);
namespace Modules\Configuration\Infrastructure\Persistence\Eloquent\Models;

use Modules\Core\Infrastructure\Persistence\Eloquent\Models\BaseModel;

class SettingModel extends BaseModel
{
    protected $table = 'settings';
    protected $fillable = ['tenant_id', 'key', 'value', 'type', 'description'];
    protected $casts = [
        'id' => 'int',
        'tenant_id' => 'int',
        'value' => 'json',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];
}
