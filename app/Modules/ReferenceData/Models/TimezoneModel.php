<?php

declare(strict_types=1);

namespace Modules\ReferenceData\Models;

use Modules\Core\Models\CoreModel;

final class TimezoneModel extends CoreModel
{
    protected $table = 'timezones';
    protected $fillable = ['name', 'display_name', 'is_active'];
    protected function casts(): array { return ['row_version' => 'integer', 'is_active' => 'boolean']; }
}
