<?php

declare(strict_types=1);

namespace Modules\Inventory\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Model;
use Modules\Inventory\Infrastructure\Persistence\Eloquent\Concerns\HasTenantAndOrganization;

class CycleCountHeader extends Model
{
    use HasTenantAndOrganization;

    protected $table = 'cycle_count_headers';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'row_version' => 'integer',
            'metadata' => 'array',
            'counted_at' => 'datetime',
            'approved_at' => 'datetime',
        ];
    }

    public function lines(): HasMany
    {
        return $this->hasMany('Modules\\Inventory\\Infrastructure\\Persistence\\Eloquent\\Models\\CycleCountLine', 'count_header_id');
    }
}
