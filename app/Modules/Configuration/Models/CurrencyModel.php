<?php

declare(strict_types=1);

namespace Modules\Configuration\Models;

use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Core\Models\CoreModel;

final class CurrencyModel extends CoreModel
{
    use SoftDeletes;

    protected $table = 'currencies';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return array_merge(parent::casts(), [
            'decimal_places' => 'integer',
            'is_active' => 'boolean',
        ]);
    }
}
