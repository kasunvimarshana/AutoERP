<?php

declare(strict_types=1);

namespace Modules\Configuration\Models;

use Modules\Core\Models\CoreModel;

final class TenantConfigurationValue extends CoreModel
{
    protected $table = 'tenant_configuration_values';
    protected $fillable = ['tenant_id', 'key', 'value', 'value_type', 'is_sensitive'];

    protected function casts(): array
    {
        return ['tenant_id' => 'integer', 'row_version' => 'integer', 'is_sensitive' => 'boolean'];
    }
}
