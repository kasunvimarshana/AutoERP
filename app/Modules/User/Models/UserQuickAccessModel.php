<?php

declare(strict_types=1);

/**
 * AutoERP Enterprise System
 *
 * @author Kasun Vimarshana <kasunvmail@gmail.com>
 *
 * Phone: +94713525253
 */

namespace Modules\User\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Core\Models\TenantOwnedModel;

final class UserQuickAccessModel extends TenantOwnedModel
{
    protected $table = 'user_quick_access_settings';

    protected $fillable = [
        'tenant_id',
        'user_id',
        'row_version',
        'items',
    ];

    protected function casts(): array
    {
        return array_merge(parent::casts(), [
            'tenant_id' => 'integer',
            'user_id' => 'integer',
            'row_version' => 'integer',
            'items' => 'array',
        ]);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(UserModel::class, 'user_id');
    }
}
