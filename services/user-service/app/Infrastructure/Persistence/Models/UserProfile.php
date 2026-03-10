<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Permission\Traits\HasRoles;

/**
 * UserProfile Eloquent Model (User Service)
 *
 * The User Service stores user profile data and role assignments.
 * Authentication is handled entirely by the Auth Service.
 *
 * @property string              $id
 * @property string              $tenant_id
 * @property string              $auth_user_id   Cross-service reference to Auth Service user
 * @property string              $name
 * @property string              $email
 * @property string|null         $phone
 * @property string|null         $avatar_url
 * @property array<string,mixed> $address
 * @property array<string,mixed> $preferences
 * @property array<string,mixed> $metadata
 * @property bool                $is_active
 */
class UserProfile extends Model
{
    use HasFactory, HasUuids, HasRoles, SoftDeletes;

    protected $table = 'user_profiles';

    protected $fillable = [
        'tenant_id',
        'auth_user_id',
        'name',
        'email',
        'phone',
        'avatar_url',
        'address',
        'preferences',
        'metadata',
        'is_active',
        'timezone',
        'locale',
    ];

    protected $casts = [
        'address'     => 'array',
        'preferences' => 'array',
        'metadata'    => 'array',
        'is_active'   => 'boolean',
    ];

    protected $hidden = ['metadata'];

    public function scopeForTenant(\Illuminate\Database\Eloquent\Builder $query, string $tenantId): \Illuminate\Database\Eloquent\Builder
    {
        return $query->where('tenant_id', $tenantId);
    }

    public function scopeActive(\Illuminate\Database\Eloquent\Builder $query): \Illuminate\Database\Eloquent\Builder
    {
        return $query->where('is_active', true);
    }
}
