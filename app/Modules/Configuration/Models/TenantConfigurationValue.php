<?php

declare(strict_types=1);

namespace Modules\Configuration\Models;

use Modules\Core\Models\TenantOwnedModel;

final class TenantConfigurationValue extends TenantOwnedModel
{
    protected $table = 'tenant_configuration_values';
    protected $fillable = ['tenant_id', 'key', 'definition_version', 'value', 'value_type', 'is_sensitive'];

    protected function casts(): array
    {
        return ['tenant_id' => 'integer', 'row_version' => 'integer', 'definition_version' => 'integer', 'is_sensitive' => 'boolean'];
    }
}
