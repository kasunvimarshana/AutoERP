<?php

declare(strict_types=1);

namespace Modules\Extension\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Modules\Extension\Infrastructure\Persistence\Eloquent\Concerns\HasTenantAndOrganization;

class EntityAttribute extends Model
{
    use HasTenantAndOrganization;

    protected $table = 'entity_attributes';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'row_version' => 'integer',
            'metadata' => 'array',
        ];
    }

    public function entity(): MorphTo
    {
        return $this->morphTo(__FUNCTION__, 'entity_type', 'entity_id');
    }
}
