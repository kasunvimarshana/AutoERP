<?php

namespace Modules\HR\Infrastructure\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class SalaryStructureLineModel extends Model
{
    protected $table = 'hr_salary_structure_lines';
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'id',
        'structure_id',
        'component_id',
        'sequence',
        'override_amount',
    ];

    protected $casts = [
        'override_amount' => 'string',
    ];

    public function component(): BelongsTo
    {
        return $this->belongsTo(SalaryComponentModel::class, 'component_id');
    }

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
