<?php

declare(strict_types=1);

namespace App\Domain\User\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Passport\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

/**
 * User Model
 *
 * Multi-tenant aware user with Passport SSO and Spatie RBAC.
 * Supports ABAC via policy-based authorization.
 *
 * @property string $id
 * @property string $tenant_id
 * @property string $name
 * @property string $email
 * @property string $password
 * @property string|null $sso_id
 * @property string|null $sso_provider
 * @property array|null $attributes Attributes for ABAC
 */
class User extends Authenticatable
{
    use HasApiTokens, HasFactory, HasRoles, HasUuids, Notifiable, SoftDeletes;

    protected $table = 'users';

    protected $fillable = [
        'tenant_id',
        'name',
        'email',
        'password',
        'sso_id',
        'sso_provider',
        'attributes',
        'status',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'attributes' => 'array',
        'deleted_at' => 'datetime',
    ];

    /**
     * Get the tenant this user belongs to.
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(\App\Domain\Tenant\Models\Tenant::class, 'tenant_id');
    }

    /**
     * Check an ABAC attribute condition.
     *
     * @param  array<string, mixed>  $conditions
     */
    public function matchesAttributes(array $conditions): bool
    {
        $userAttributes = $this->getAttribute('attributes') ?? [];

        foreach ($conditions as $key => $value) {
            if (!isset($userAttributes[$key]) || $userAttributes[$key] !== $value) {
                return false;
            }
        }

        return true;
    }
}
