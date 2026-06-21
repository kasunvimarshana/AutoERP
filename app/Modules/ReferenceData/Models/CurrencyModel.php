<?php

declare(strict_types=1);

namespace Modules\ReferenceData\Models;

use Modules\Core\Models\CoreModel;

final class CurrencyModel extends CoreModel
{
    protected $table = 'currencies';
    protected $fillable = ['code', 'name', 'symbol', 'decimal_places', 'is_active'];
    protected function casts(): array { return ['row_version' => 'integer', 'decimal_places' => 'integer', 'is_active' => 'boolean']; }
}
