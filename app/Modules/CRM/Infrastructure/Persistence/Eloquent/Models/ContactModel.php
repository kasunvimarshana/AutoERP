<?php

declare(strict_types=1);

namespace Modules\CRM\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Relations\MorphTo;
use Modules\Core\Infrastructure\Persistence\Eloquent\Models\BaseModel;
use Modules\Core\Infrastructure\Persistence\Eloquent\Traits\HasTenant;
use Modules\Core\Infrastructure\Persistence\Eloquent\Traits\HasUuid;

class ContactModel extends BaseModel
{
    use HasUuid, HasTenant;

    protected $table = 'contacts';

    protected $fillable = [
        'tenant_id',
        'contactable_type',
        'contactable_id',
        'first_name',
        'last_name',
        'title',
        'department',
        'position',
        'email',
        'phone',
        'mobile',
        'is_primary',
        'notes',
    ];

    protected $casts = [
        'is_primary' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * Polymorphic owner (Customer or Supplier).
     */
    public function contactable(): MorphTo
    {
        return $this->morphTo();
    }
}
