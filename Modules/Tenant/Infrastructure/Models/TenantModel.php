<?php
namespace Modules\Tenant\Infrastructure\Models;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
class TenantModel extends Model
{
    use HasUuids, SoftDeletes;
    protected $table = 'tenants';
    protected $fillable = [
        'id', 'name', 'slug', 'domain', 'status',
        'timezone', 'default_currency', 'locale',
        'logo_path', 'fiscal_year_start', 'metadata',
    ];
    protected $casts = ['metadata' => 'array'];
}
