<?php

declare(strict_types=1);

namespace Modules\ReferenceData\Models;

use Modules\Core\Models\CoreModel;

final class CountryModel extends CoreModel
{
    protected $table = 'countries';
    protected $fillable = ['code', 'name', 'phone_code', 'is_active'];
    protected function casts(): array { return ['row_version' => 'integer', 'is_active' => 'boolean']; }
}
