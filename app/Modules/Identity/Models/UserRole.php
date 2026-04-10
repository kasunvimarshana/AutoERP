<?php

namespace App\Modules\Identity\Models;

use BaseModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class UserRole extends BaseModel
{
    protected $table = 'user_roles';

    protected $fillable = [
        'user_id',
        'role_id',
        'org_unit_id',
        'assigned_at'
    ];

    protected $casts = [
        'assigned_at' => 'datetime'
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(\App\Modules\Identity\Models\User::class, 'user_id');
    }

    public function orgUnit(): BelongsTo
    {
        return $this->belongsTo(\App\Modules\Core\Models\Organization::class, 'org_unit_id');
    }
}
