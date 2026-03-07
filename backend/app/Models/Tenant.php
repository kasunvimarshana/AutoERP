<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Tenant extends Model
{
    use SoftDeletes;

    protected $fillable = ['name', 'domain', 'database', 'settings', 'is_active'];

    protected $casts = [
        'settings' => 'array',
        'is_active' => 'boolean',
    ];

    public function users()
    {
        return $this->hasMany(User::class);
    }

    public function configs()
    {
        return $this->hasMany(TenantConfig::class);
    }

    public function getConfig(string $key, $default = null)
    {
        $config = $this->configs()->where('key', $key)->first();
        return $config ? $config->value : $default;
    }
}
