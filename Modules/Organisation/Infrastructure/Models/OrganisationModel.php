<?php

declare(strict_types=1);

namespace Modules\Organisation\Infrastructure\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Core\Infrastructure\Traits\BelongsToTenant;

class OrganisationModel extends Model
{
    use BelongsToTenant;
    use SoftDeletes;

    protected $table = 'organisations';

    protected $fillable = [
        'tenant_id',
        'parent_id',
        'type',
        'name',
        'code',
        'description',
        'status',
        'meta',
    ];

    protected $casts = [
        'meta' => 'array',
    ];

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id');
    }
}
