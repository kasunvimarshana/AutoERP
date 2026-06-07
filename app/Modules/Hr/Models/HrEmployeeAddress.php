<?php
declare(strict_types=1);
namespace Modules\Hr\Models;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Core\Models\CoreModel;
use Modules\Hr\Enums\EmployeeAddressType;
final class HrEmployeeAddress extends CoreModel
{
    use SoftDeletes;
    protected $table = 'hr_employee_addresses';
    protected $guarded = ['id'];
    protected function casts(): array { return array_merge(parent::casts(), ['tenant_id' => 'integer', 'organization_unit_id' => 'integer', 'employee_id' => 'integer', 'address_type' => EmployeeAddressType::class, 'is_primary' => 'boolean', 'is_active' => 'boolean']); }
    public function employee(): BelongsTo { return $this->belongsTo(HrEmployee::class, 'employee_id'); }
}
