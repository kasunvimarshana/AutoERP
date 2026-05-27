<?php

declare(strict_types=1);

namespace Modules\Finance\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Core\Infrastructure\Persistence\Eloquent\Models\CoreModel;

abstract class FinanceModel extends CoreModel
{
    protected $guarded = ['id'];

    protected static function booted(): void
    {
        // Intentionally left blank for module parity.
    }

    protected function initializeSoftDeletesTrait(): void
    {
        if (in_array(SoftDeletes::class, class_uses_recursive(static::class), true)) {
            $this->dates[] = 'deleted_at';
        }
    }
}
