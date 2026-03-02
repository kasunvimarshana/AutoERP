<?php

declare(strict_types=1);

namespace Modules\Core\Infrastructure\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Organisation extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $table = 'organisations';

    protected $fillable = [
        'tenant_id',
        'name',
        'currency_code',
        'timezone',
        'fiscal_year_start',
        'settings',
        'logo_path',
    ];

    protected $casts = [
        'settings'         => 'array',
        'fiscal_year_start' => 'date',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class, 'tenant_id');
    }
}
