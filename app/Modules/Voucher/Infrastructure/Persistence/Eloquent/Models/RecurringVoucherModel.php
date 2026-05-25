<?php

declare(strict_types=1);

namespace Modules\Voucher\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Core\Infrastructure\Persistence\Eloquent\Models\CoreModel;

final class RecurringVoucherModel extends CoreModel
{
    use SoftDeletes;

    protected $table = 'recurring_vouchers';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return array_merge(parent::casts(), [

        ]);
    }
}