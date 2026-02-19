<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LeaveType extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'tenant_id', 'name', 'code', 'days_per_year', 'is_paid', 'is_active',
    ];

    protected function casts(): array
    {
        return [
            'days_per_year' => 'decimal:2',
            'is_paid' => 'boolean',
            'is_active' => 'boolean',
        ];
    }
}
