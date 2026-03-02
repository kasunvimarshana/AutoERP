<?php

declare(strict_types=1);

namespace Modules\Customization\Infrastructure\Models;

use Illuminate\Database\Eloquent\Model;
use Modules\Core\Infrastructure\Traits\BelongsToTenant;

class CustomFieldValueModel extends Model
{
    use BelongsToTenant;

    protected $table = 'custom_field_values';

    protected $fillable = [
        'tenant_id',
        'entity_type',
        'entity_id',
        'field_id',
        'value',
    ];
}
