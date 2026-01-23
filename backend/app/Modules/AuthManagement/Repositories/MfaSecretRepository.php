<?php

namespace App\Modules\AuthManagement\Repositories;

use App\Core\Base\BaseRepository;
use App\Modules\AuthManagement\Models/MfaSecret;

class MfaSecretRepository extends BaseRepository
{
    public function __construct(MfaSecret $model)
    {
        parent::__construct($model);
    }

    /**
     * Find MFA secret by user ID and type
     */
    public function findByUserAndType(int $userId, string $type = 'totp'): ?MfaSecret
    {
        return $this->model
            ->where('user_id', $userId)
            ->where('type', $type)
            ->first();
    }

    /**
     * Find or create MFA secret for user
     */
    public function findOrCreate(int $userId, string $type = 'totp'): MfaSecret
    {
        $mfaSecret = $this->findByUserAndType($userId, $type);
        
        if (!$mfaSecret) {
            $mfaSecret = $this->create([
                'user_id' => $userId,
                'type' => $type,
                'is_enabled' => false,
            ]);
        }
        
        return $mfaSecret;
    }

    /**
     * Get enabled MFA methods for user
     */
    public function getEnabledMethods(int $userId): \Illuminate\Database\Eloquent\Collection
    {
        return $this->model
            ->where('user_id', $userId)
            ->where('is_enabled', true)
            ->get();
    }

    /**
     * Disable all MFA methods for user
     */
    public function disableAllForUser(int $userId): int
    {
        return $this->model
            ->where('user_id', $userId)
            ->update([
                'is_enabled' => false,
                'enabled_at' => null,
            ]);
    }
}
