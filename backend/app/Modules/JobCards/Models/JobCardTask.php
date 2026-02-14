<?php

namespace App\Modules\JobCards\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class JobCardTask extends Model
{
    use HasFactory, SoftDeletes, LogsActivity;

    protected $table = 'job_card_tasks';

    protected $fillable = [
        'tenant_id',
        'job_card_id',
        'sku_id',
        'task_type',
        'description',
        'quantity',
        'unit_price',
        'discount',
        'tax_rate',
        'line_total',
        'status',
        'completed_by',
        'completed_at',
    ];

    protected $casts = [
        'quantity' => 'decimal:2',
        'unit_price' => 'decimal:2',
        'discount' => 'decimal:2',
        'tax_rate' => 'decimal:2',
        'line_total' => 'decimal:2',
        'completed_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly([
                'job_card_id',
                'sku_id',
                'task_type',
                'description',
                'quantity',
                'unit_price',
                'discount',
                'tax_rate',
                'line_total',
                'status',
                'completed_by',
                'completed_at',
            ])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    public function tenant()
    {
        return $this->belongsTo(\App\Modules\Tenancy\Models\Tenant::class);
    }

    public function jobCard()
    {
        return $this->belongsTo(JobCard::class);
    }

    public function sku()
    {
        return $this->belongsTo(\App\Modules\Inventory\Models\SKU::class);
    }

    public function completedBy()
    {
        return $this->belongsTo(\App\Modules\Identity\Models\User::class, 'completed_by');
    }
}
