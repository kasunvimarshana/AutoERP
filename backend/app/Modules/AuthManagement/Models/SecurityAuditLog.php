<?php

namespace App\Modules\AuthManagement\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\User;
use App\Modules\TenantManagement\Models\Tenant;

class SecurityAuditLog extends Model
{
    protected $fillable = [
        'user_id',
        'tenant_id',
        'event_type',
        'severity',
        'description',
        'ip_address',
        'user_agent',
        'metadata',
        'occurred_at',
    ];

    protected $casts = [
        'metadata' => 'array',
        'occurred_at' => 'datetime',
    ];

    // Event types
    public const EVENT_LOGIN_SUCCESS = 'login_success';
    public const EVENT_LOGIN_FAILED = 'login_failed';
    public const EVENT_LOGOUT = 'logout';
    public const EVENT_PASSWORD_CHANGE = 'password_change';
    public const EVENT_PASSWORD_RESET_REQUESTED = 'password_reset_requested';
    public const EVENT_PASSWORD_RESET_COMPLETED = 'password_reset_completed';
    public const EVENT_MFA_ENABLED = 'mfa_enabled';
    public const EVENT_MFA_DISABLED = 'mfa_disabled';
    public const EVENT_MFA_VERIFIED = 'mfa_verified';
    public const EVENT_ACCOUNT_LOCKED = 'account_locked';
    public const EVENT_ACCOUNT_UNLOCKED = 'account_unlocked';
    public const EVENT_TOKEN_REFRESH = 'token_refresh';
    public const EVENT_SUSPICIOUS_ACTIVITY = 'suspicious_activity';

    // Severity levels
    public const SEVERITY_INFO = 'info';
    public const SEVERITY_WARNING = 'warning';
    public const SEVERITY_CRITICAL = 'critical';

    /**
     * Get the user associated with this log
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the tenant associated with this log
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * Create a security audit log entry
     */
    public static function logEvent(
        string $eventType,
        ?int $userId = null,
        ?int $tenantId = null,
        string $description = '',
        string $severity = self::SEVERITY_INFO,
        array $metadata = []
    ): self {
        return self::create([
            'user_id' => $userId,
            'tenant_id' => $tenantId,
            'event_type' => $eventType,
            'severity' => $severity,
            'description' => $description,
            'ip_address' => request()->ip() ?? '0.0.0.0',
            'user_agent' => request()->userAgent() ?? 'Unknown',
            'metadata' => $metadata,
            'occurred_at' => now(),
        ]);
    }
}
