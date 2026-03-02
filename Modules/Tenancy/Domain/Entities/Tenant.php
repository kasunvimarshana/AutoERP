<?php

declare(strict_types=1);

namespace Modules\Tenancy\Domain\Entities;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Tenant entity.
 *
 * The top-level isolation unit of the platform.
 * All business data is scoped beneath a Tenant.
 *
 * @property int         $id
 * @property string      $name
 * @property string      $slug
 * @property string|null $domain
 * @property bool        $is_active
 * @property bool        $pharma_compliance_mode
 * @property array|null  $settings
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property \Carbon\Carbon|null $deleted_at
 */
class Tenant extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $table = 'tenants';

    protected $fillable = [
        'name',
        'slug',
        'domain',
        'is_active',
        'pharma_compliance_mode',
        'settings',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'pharma_compliance_mode' => 'boolean',
            'settings' => 'array',
        ];
    }

    /**
     * Determine whether pharmaceutical compliance mode is enabled for this tenant.
     */
    public function hasPharmaceuticalComplianceMode(): bool
    {
        return $this->pharma_compliance_mode === true;
    }
}
