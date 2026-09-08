<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use LogicException;
use Modules\Core\Models\TenantOwnedModel;
use Modules\User\Models\UserModel;

abstract class AgreementHistory extends TenantOwnedModel
{
    public $timestamps = false;

    protected static function booted(): void
    {
        static::updating(static fn () => throw new LogicException('Agreement history is append-only.'));
        static::deleting(static fn () => throw new LogicException('Agreement history is append-only.'));
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(UserModel::class, 'actor_id')->withTrashed();
    }

    protected function casts(): array
    {
        return array_merge(parent::casts(), ['snapshot' => 'array', 'recorded_at' => 'immutable_datetime', 'row_version' => 'integer']);
    }
}
