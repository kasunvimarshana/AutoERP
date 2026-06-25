<?php

declare(strict_types=1);

namespace Modules\Configuration\Models;

use Modules\Core\Models\CoreModel;

final class GlobalConfigurationValue extends CoreModel
{
    protected $table = 'global_configuration_values';
    protected $fillable = ['key', 'definition_version', 'value', 'value_type', 'is_sensitive'];

    protected function casts(): array
    {
        return ['row_version' => 'integer', 'definition_version' => 'integer', 'is_sensitive' => 'boolean'];
    }
}
