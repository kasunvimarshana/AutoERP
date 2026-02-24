<?php

namespace Modules\Helpdesk\Infrastructure\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use Modules\Shared\Infrastructure\Traits\HasTenantScope;

class TicketModel extends Model
{
    use HasTenantScope, SoftDeletes;

    protected $table = 'helpdesk_tickets';
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'id',
        'tenant_id',
        'category_id',
        'subject',
        'description',
        'reporter_id',
        'assigned_to',
        'resolver_id',
        'priority',
        'status',
        'resolution_notes',
        'sla_due_at',
        'resolved_at',
        'closed_at',
    ];

    protected $casts = [
        'sla_due_at'  => 'datetime',
        'resolved_at' => 'datetime',
        'closed_at'   => 'datetime',
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
