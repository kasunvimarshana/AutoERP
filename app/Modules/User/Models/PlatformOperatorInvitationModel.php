<?php

declare(strict_types=1);

namespace Modules\User\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use LogicException;
use Modules\Core\Models\CoreModel;

final class PlatformOperatorInvitationModel extends CoreModel
{
    protected $table = 'platform_operator_invitations';

    protected $fillable = [
        'public_id',
        'user_id',
        'created_by_user_id',
        'token_hash',
        'delivery_token',
        'status',
        'delivery_status',
        'processing_attempt_count',
        'claim_token',
        'claimed_at',
        'lease_expires_at',
        'expires_at',
        'sent_at',
        'accepted_at',
        'revoked_at',
        'failed_at',
        'mail_provider',
        'provider_message_id',
        'error_code',
        'error_message',
        'row_version',
    ];

    protected static function booted(): void
    {
        static::updating(function (self $invitation): void {
            foreach (['user_id', 'token_hash', 'created_by_user_id'] as $attribute) {
                if ($invitation->isDirty($attribute)) {
                    throw new LogicException("Platform operator invitation {$attribute} is immutable.");
                }
            }
        });
    }

    protected function casts(): array
    {
        return array_merge(parent::casts(), [
            'delivery_token' => 'encrypted',
            'processing_attempt_count' => 'integer',
            'row_version' => 'integer',
            'claimed_at' => 'datetime',
            'lease_expires_at' => 'datetime',
            'expires_at' => 'datetime',
            'sent_at' => 'datetime',
            'accepted_at' => 'datetime',
            'revoked_at' => 'datetime',
            'failed_at' => 'datetime',
        ]);
    }

    public function operator(): BelongsTo
    {
        return $this->belongsTo(UserModel::class, 'user_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(UserModel::class, 'created_by_user_id');
    }
}
