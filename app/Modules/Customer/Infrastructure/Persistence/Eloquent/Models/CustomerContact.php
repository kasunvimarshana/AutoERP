<?php

declare(strict_types=1);

namespace Modules\Customer\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Customer\Infrastructure\Persistence\Eloquent\Concerns\HasTenantAndOrganization;
use Modules\Customer\Infrastructure\Persistence\Eloquent\Models\Customer;

class CustomerContact extends Model
{
    use HasTenantAndOrganization;

    protected $table = 'customer_contacts';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'row_version' => 'integer',
            'metadata' => 'array',
            'is_primary' => 'boolean',
        ];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'customer_id');
    }
}
