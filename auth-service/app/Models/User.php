<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Passport\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

/**
 * User model with multi-tenancy, Passport SSO, and RBAC support.
 *
 * @property string      $id
 * @property string      $tenant_id
 * @property string      $name
 * @property string      $email
 * @property string      $password
 * @property bool        $is_active
 * @property array|null  $attributes   ABAC user-level attributes.
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property \Carbon\Carbon|null $deleted_at
 */
class User extends Authenticatable
{
    use HasApiTokens, HasFactory, HasRoles, HasUuids, Notifiable, SoftDeletes;

    /** @var array<string> */
    protected $fillable = [
        'tenant_id',
        'name',
        'email',
        'password',
        'is_active',
        'attributes',
    ];

    /** @var array<string> */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /** @var array<string, string> */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password'          => 'hashed',
        'is_active'         => 'boolean',
        'attributes'        => 'array',
    ];

    /**
     * Tenant this user belongs to.
     *
     * @return BelongsTo<Tenant, User>
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }
}
