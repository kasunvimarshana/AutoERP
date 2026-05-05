<?php

declare(strict_types=1);

namespace Modules\Tax\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Audit\Infrastructure\Persistence\Eloquent\Traits\HasAudit;
use Modules\Tenant\Infrastructure\Persistence\Eloquent\Traits\HasTenant;

class TaxGroupModel extends Model
{
    use HasAudit;
    use HasTenant;

    protected $table = 'tax_groups';

    protected $fillable = [
        'tenant_id',
        'name',
        'description',
        'row_version',
    ];

    protected $casts = [
        'tenant_id' => 'integer', 'row_version' => 'integer',
    ];

    public function rates(): HasMany
    {
        return $this->hasMany(TaxRateModel::class, 'tax_group_id');
    }

    public function rules(): HasMany
    {
        return $this->hasMany(TaxRuleModel::class, 'tax_group_id');
    }
}
