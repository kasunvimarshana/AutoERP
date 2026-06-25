<?php

declare(strict_types=1);

namespace Modules\Auth\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Core\Models\TenantOwnedModel;

final class AuthRegistrationInvitationDeliveryModel extends TenantOwnedModel
{
    protected $table = 'auth_registration_invitation_deliveries';

    protected $fillable = [
        'public_id',
        'row_version',
        'tenant_id',
        'invitation_id',
        'attempt_number',
        'status',
        'processing_attempt_count',
        'claim_token',
        'claimed_at',
        'lease_expires_at',
        'requested_at',
        'sent_at',
        'delivered_at',
        'bounced_at',
        'failed_at',
        'cancelled_at',
        'provider',
        'provider_message_id',
        'error_code',
        'error_message',
    ];

    protected function casts(): array
    {
        return array_merge(parent::casts(), [
            'row_version' => 'integer',
            'tenant_id' => 'integer',
            'invitation_id' => 'integer',
            'attempt_number' => 'integer',
            'processing_attempt_count' => 'integer',
            'claimed_at' => 'datetime',
            'lease_expires_at' => 'datetime',
            'requested_at' => 'datetime',
            'sent_at' => 'datetime',
            'delivered_at' => 'datetime',
            'bounced_at' => 'datetime',
            'failed_at' => 'datetime',
            'cancelled_at' => 'datetime',
        ]);
    }

    public function invitation(): BelongsTo
    {
        return $this->belongsTo(AuthRegistrationInvitationModel::class, 'invitation_id');
    }
}
