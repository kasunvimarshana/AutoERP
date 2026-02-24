<?php

namespace Modules\HR\Infrastructure\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use Modules\Shared\Infrastructure\Traits\HasTenantScope;

class SalaryStructureAssignmentModel extends Model
{
    use HasTenantScope, SoftDeletes;

    protected $table = 'hr_salary_structure_assignments';
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'id',
        'tenant_id',
        'employee_id',
        'structure_id',
        'base_amount',
        'effective_from',
    ];

    protected $casts = [
        'base_amount'    => 'string',
        'effective_from' => 'date',
    ];

    protected static function boot(): void
    {
        parent::boot();
        static::creating(function (self $model) {
            if (empty($model->id)) {
                $model->id = (string) Str::uuid();
            }
        });
    }
}
