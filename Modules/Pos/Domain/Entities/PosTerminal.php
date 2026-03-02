<?php

declare(strict_types=1);

namespace Modules\POS\Domain\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Core\Domain\Traits\HasTenant;

/**
 * PosTerminal entity.
 */
class PosTerminal extends Model
{
    use HasTenant;

    protected $table = 'pos_terminals';

    protected $fillable = [
        'tenant_id',
        'name',
        'terminal_code',
        'location_id',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function sessions(): HasMany
    {
        return $this->hasMany(PosSession::class, 'terminal_id');
    }
}
