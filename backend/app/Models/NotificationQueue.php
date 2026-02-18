<?php

namespace App\Models;

use App\Traits\TenantScoped;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NotificationQueue extends Model
{
    use HasFactory, TenantScoped;

    protected $table = 'notification_queue';

    protected $fillable = [
        'tenant_id',
        'user_id',
        'channel',
        'type',
        'data',
        'status',
        'attempts',
        'error_message',
        'scheduled_at',
        'sent_at',
    ];

    protected $casts = [
        'data' => 'array',
        'scheduled_at' => 'datetime',
        'sent_at' => 'datetime',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function markAsSent(): void
    {
        $this->update([
            'status' => 'sent',
            'sent_at' => now(),
        ]);
    }

    public function markAsFailed(string $errorMessage): void
    {
        $this->update([
            'status' => 'failed',
            'attempts' => $this->attempts + 1,
            'error_message' => $errorMessage,
        ]);
    }

    public function canRetry(int $maxAttempts = 3): bool
    {
        return $this->attempts < $maxAttempts && $this->status !== 'sent';
    }
}
