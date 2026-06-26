<?php

declare(strict_types=1);

namespace Modules\User\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Core\Models\CoreModel;

final class PlatformOperatorInvitationDeliveryModel extends CoreModel
{
    protected $table = 'platform_operator_invitation_deliveries';
    protected $fillable = [
        'invitation_id', 'attempt_number', 'status', 'claim_token', 'claimed_at',
        'lease_expires_at', 'sent_at', 'failed_at', 'mail_provider',
        'provider_message_id', 'error_code', 'error_message', 'row_version',
    ];

    protected function casts(): array
    {
        return array_merge(parent::casts(), [
            'attempt_number' => 'integer', 'row_version' => 'integer',
            'claimed_at' => 'datetime', 'lease_expires_at' => 'datetime',
            'sent_at' => 'datetime', 'failed_at' => 'datetime',
        ]);
    }

    public function invitation(): BelongsTo { return $this->belongsTo(PlatformOperatorInvitationModel::class, 'invitation_id'); }
}
