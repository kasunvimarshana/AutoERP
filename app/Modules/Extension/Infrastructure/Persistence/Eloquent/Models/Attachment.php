<?php

declare(strict_types=1);

namespace Modules\Extension\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Modules\Extension\Infrastructure\Persistence\Eloquent\Concerns\HasTenantAndOrganization;

class Attachment extends Model
{
    use HasTenantAndOrganization;

    protected $table = 'attachments';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'row_version' => 'integer',
            'metadata' => 'array',
            'size' => 'integer',
        ];
    }

    public function attachable(): MorphTo
    {
        return $this->morphTo(__FUNCTION__, 'attachable_type', 'attachable_id');
    }
}
