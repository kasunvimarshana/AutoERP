<?php

namespace Modules\HR\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Model;

class PayslipLineModel extends Model
{
    protected $table = 'payslip_lines';
    protected $guarded = [];
}
