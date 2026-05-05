<?php

declare(strict_types=1);

namespace Modules\HR\Infrastructure\Persistence\Eloquent\Models;

use Modules\Core\Infrastructure\Persistence\Eloquent\Models\BaseModel;

class PayslipLineModel extends BaseModel
{
    protected $table = 'hr_payslip_lines';

    protected $fillable = ['payslip_id', 'payroll_item_id', 'item_name', 'item_code', 'type', 'amount', 'metadata'];

    protected $casts = ['metadata' => 'array'];
}
