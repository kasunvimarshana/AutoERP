<?php

declare(strict_types=1);

namespace Modules\User\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use LogicException;
use Modules\Core\Models\CoreModel;

final class PlatformOperatorInvitationModel extends CoreModel
{
    protected $table = 'platform_operator_invitations';
    protected $fillable = [
        'public_id', 'platform_operator_id', 'created_by_operator_id', 'token_hash',
        'delivery_token', 'status', 'expires_at', 'accepted_at', 'revoked_at',
        'revocation_reason', 'row_version',
    ];
    protected $hidden = ['token_hash', 'delivery_token'];

    protected static function booted(): void
    {
        static::updating(function (self $invitation): void {
            if ($invitation->isDirty(['platform_operator_id', 'token_hash', 'created_by_operator_id'])) {
                throw new LogicException('Platform operator invitation ownership and token identity are immutable.');
            }
        });
    }

    protected function casts(): array
    {
        return array_merge(parent::casts(), [
            'delivery_token' => 'encrypted', 'row_version' => 'integer',
            'expires_at' => 'datetime', 'accepted_at' => 'datetime', 'revoked_at' => 'datetime',
        ]);
    }

    public function operator(): BelongsTo { return $this->belongsTo(PlatformOperatorModel::class, 'platform_operator_id'); }
    public function createdBy(): BelongsTo { return $this->belongsTo(PlatformOperatorModel::class, 'created_by_operator_id'); }
    public function deliveries(): HasMany { return $this->hasMany(PlatformOperatorInvitationDeliveryModel::class, 'invitation_id'); }
}
