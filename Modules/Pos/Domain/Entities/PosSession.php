<?php

declare(strict_types=1);

namespace Modules\POS\Domain\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Core\Domain\Traits\HasTenant;

/**
 * PosSession entity.
 *
 * Monetary float values are cast to string for BCMath precision.
 */
class PosSession extends Model
{
    use HasTenant;

    protected $table = 'pos_sessions';

    protected $fillable = [
        'tenant_id',
        'terminal_id',
        'cashier_id',
        'opened_at',
        'closed_at',
        'opening_float',
        'closing_float',
        'status',
    ];

    protected $casts = [
        'opened_at'     => 'datetime',
        'closed_at'     => 'datetime',
        'opening_float' => 'string',
        'closing_float' => 'string',
    ];

    public function terminal(): BelongsTo
    {
        return $this->belongsTo(PosTerminal::class, 'terminal_id');
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(PosTransaction::class, 'pos_session_id');
    }
}
