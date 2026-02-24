<?php

namespace Modules\Budget\Infrastructure\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class BudgetLineModel extends Model
{
    protected $table = 'budget_lines';
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'id',
        'tenant_id',
        'budget_id',
        'category',
        'description',
        'planned_amount',
        'actual_amount',
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
