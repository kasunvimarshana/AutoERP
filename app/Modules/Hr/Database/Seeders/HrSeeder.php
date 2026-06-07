<?php
declare(strict_types=1);
namespace Modules\Hr\Database\Seeders;
use Illuminate\Database\Seeder;
use Modules\Hr\Models\HrCertification;
use Modules\Hr\Models\HrDepartment;
use Modules\Hr\Models\HrDesignation;
use Modules\Hr\Models\HrEmploymentType;
use Modules\Hr\Models\HrLicense;
use Modules\Hr\Models\HrSkill;
use Modules\Tenant\Models\TenantModel;
final class HrSeeder extends Seeder
{
    public function run(): void
    {
        foreach (TenantModel::query()->pluck('id') as $tenantId) {
            foreach ([[HrDepartment::class, 'SERVICE', 'Service'], [HrDepartment::class, 'ADMIN', 'Administration'], [HrDesignation::class, 'TECHNICIAN', 'Technician'], [HrDesignation::class, 'SERVICE-ADVISOR', 'Service Advisor'], [HrEmploymentType::class, 'FULL-TIME', 'Full Time'], [HrEmploymentType::class, 'CONTRACT', 'Contract'], [HrSkill::class, 'GENERAL-SERVICE', 'General Service'], [HrSkill::class, 'DIAGNOSTICS', 'Diagnostics'], [HrCertification::class, 'AUTOMOTIVE-TECH', 'Automotive Technician'], [HrLicense::class, 'DRIVING', 'Driving License']] as [$model, $code, $name]) {
                $values = ['name' => $name, 'is_active' => true];
                if (in_array($model, [HrDepartment::class, HrDesignation::class, HrEmploymentType::class], true)) {
                    $values['sort_order'] = 0;
                }
                $model::query()->firstOrCreate(['tenant_id' => $tenantId, 'code' => $code], $values);
            }
        }
    }
}
