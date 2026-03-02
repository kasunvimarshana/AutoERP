<?php

declare(strict_types=1);

namespace Modules\Notification\Domain\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Core\Domain\Traits\HasTenant;

/**
 * NotificationTemplate entity.
 *
 * Represents a reusable, tenant-scoped notification template
 * for any supported delivery channel.
 */
class NotificationTemplate extends Model
{
    use HasTenant;
    use SoftDeletes;

    protected $table = 'notification_templates';

    protected $fillable = [
        'tenant_id',
        'name',
        'slug',
        'channel',
        'subject',
        'body_template',
        'variables',
        'is_active',
    ];

    protected $casts = [
        'variables' => 'array',
        'is_active' => 'boolean',
    ];

    public function logs(): HasMany
    {
        return $this->hasMany(NotificationLog::class, 'notification_template_id');
    }
}
