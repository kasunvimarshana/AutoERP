<?php

namespace App\Modules\AuthManagement\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\User;

class MfaSecret extends Model
{
    protected $fillable = [
        'user_id',
        'secret',
        'type',
        'is_enabled',
        'enabled_at',
        'recovery_codes',
        'last_used_at',
    ];

    protected $casts = [
        'is_enabled' => 'boolean',
        'enabled_at' => 'datetime',
        'recovery_codes' => 'array',
        'last_used_at' => 'datetime',
    ];

    protected $hidden = [
        'secret',
        'recovery_codes',
    ];

    /**
     * Get the user that owns this MFA secret
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Generate recovery codes
     */
    public function generateRecoveryCodes(int $count = 8): array
    {
        $codes = [];
        for ($i = 0; $i < $count; $i++) {
            $codes[] = strtoupper(substr(str_replace(['+', '/', '='], '', base64_encode(random_bytes(6))), 0, 8));
        }
        
        return $codes;
    }

    /**
     * Check if a recovery code is valid
     */
    public function verifyRecoveryCode(string $code): bool
    {
        $codes = $this->recovery_codes ?? [];
        $index = array_search(strtoupper($code), $codes, true);
        
        if ($index !== false) {
            // Remove used recovery code
            unset($codes[$index]);
            $this->recovery_codes = array_values($codes);
            $this->save();
            
            return true;
        }
        
        return false;
    }
}
