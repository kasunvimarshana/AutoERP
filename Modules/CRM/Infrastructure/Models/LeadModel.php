<?php

declare(strict_types=1);

namespace Modules\Crm\Infrastructure\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Core\Infrastructure\Traits\BelongsToTenant;

class LeadModel extends Model
{
    use BelongsToTenant;
    use SoftDeletes;

    protected $table = 'crm_leads';

    protected $fillable = [
        'tenant_id',
        'contact_id',
        'title',
        'description',
        'status',
        'estimated_value',
        'currency',
        'expected_close_date',
        'notes',
    ];

    protected $casts = [
        'expected_close_date' => 'date',
    ];

    public function contact(): BelongsTo
    {
        return $this->belongsTo(ContactModel::class, 'contact_id');
    }
}
