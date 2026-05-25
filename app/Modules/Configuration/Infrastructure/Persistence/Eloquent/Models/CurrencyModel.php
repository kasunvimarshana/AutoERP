<?php

declare(strict_types=1);

namespace Modules\Configuration\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Core\Infrastructure\Persistence\Eloquent\Models\CoreModel;

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
