<?php

declare(strict_types=1);

namespace Modules\Auth\Repositories;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Modules\Auth\Models\RevokedToken;
use Modules\Core\Repositories\BaseRepository;

/**
 * RevokedToken Repository
 *
 * Handles token blacklist operations
 */
class RevokedTokenRepository extends BaseRepository
{
    /**
     * Make a new RevokedToken model instance
     */
    protected function makeModel(): Model
    {
        return new RevokedToken;
    }

    /**
     * Find revoked token by token ID
     */
    public function findByTokenId(string $tokenId): ?RevokedToken
    {
        return $this->model
            ->where('token_id', $tokenId)
            ->first();
    }

    /**
     * Check if token is revoked
     */
    public function isRevoked(string $tokenId): bool
    {
        return $this->exists(['token_id' => $tokenId]);
    }

    /**
     * Revoke token
     */
    public function revokeToken(string $tokenId, Carbon $expiresAt): RevokedToken
    {
        return $this->firstOrCreate(
            ['token_id' => $tokenId],
            ['expires_at' => $expiresAt]
        );
    }

    /**
     * Revoke multiple tokens
     */
    public function revokeTokens(array $tokenIds, Carbon $expiresAt): void
    {
        $records = array_map(function ($tokenId) use ($expiresAt) {
            return [
                'token_id' => $tokenId,
                'expires_at' => $expiresAt,
                'created_at' => now(),
            ];
        }, $tokenIds);

        $this->bulkInsert($records);
    }

    /**
     * Delete expired tokens
     */
    public function deleteExpired(): int
    {
        return $this->model
            ->where('expires_at', '<', now())
            ->delete();
    }

    /**
     * Delete token by token ID
     */
    public function deleteByTokenId(string $tokenId): bool
    {
        $token = $this->findByTokenId($tokenId);

        if (! $token) {
            return false;
        }

        return $token->delete();
    }

    /**
     * Count revoked tokens
     */
    public function countRevoked(): int
    {
        return $this->model->count();
    }

    /**
     * Count expired tokens
     */
    public function countExpired(): int
    {
        return $this->model
            ->where('expires_at', '<', now())
            ->count();
    }

    /**
     * Count active revoked tokens
     */
    public function countActive(): int
    {
        return $this->model
            ->where('expires_at', '>=', now())
            ->count();
    }

    /**
     * Get tokens expiring soon
     */
    public function getExpiringSoon(int $hours = 24): \Illuminate\Database\Eloquent\Collection
    {
        $expiryTime = Carbon::now()->addHours($hours);

        return $this->model
            ->where('expires_at', '>=', now())
            ->where('expires_at', '<=', $expiryTime)
            ->get();
    }

    /**
     * Cleanup expired tokens
     */
    public function cleanup(): int
    {
        return $this->deleteExpired();
    }

    /**
     * Purge all revoked tokens
     */
    public function purgeAll(): int
    {
        return $this->model->delete();
    }

    /**
     * Get oldest revoked token
     */
    public function getOldest(): ?RevokedToken
    {
        return $this->model
            ->orderBy('created_at', 'asc')
            ->first();
    }

    /**
     * Get newest revoked token
     */
    public function getNewest(): ?RevokedToken
    {
        return $this->model
            ->orderBy('created_at', 'desc')
            ->first();
    }

    /**
     * Check if token will expire soon
     */
    public function willExpireSoon(string $tokenId, int $hours = 1): bool
    {
        $token = $this->findByTokenId($tokenId);

        if (! $token) {
            return false;
        }

        $expiryTime = Carbon::now()->addHours($hours);

        return $token->expires_at <= $expiryTime;
    }

    /**
     * Get tokens by expiry date range
     */
    public function getByExpiryRange(Carbon $startDate, Carbon $endDate): \Illuminate\Database\Eloquent\Collection
    {
        return $this->model
            ->where('expires_at', '>=', $startDate)
            ->where('expires_at', '<=', $endDate)
            ->orderBy('expires_at', 'asc')
            ->get();
    }

    /**
     * Bulk revoke tokens with same expiry
     */
    public function bulkRevoke(array $tokenIds, Carbon $expiresAt): int
    {
        $this->beginTransaction();
        try {
            $records = collect($tokenIds)->map(function ($tokenId) use ($expiresAt) {
                return [
                    'token_id' => $tokenId,
                    'expires_at' => $expiresAt,
                    'created_at' => now(),
                ];
            })->toArray();

            $result = $this->bulkInsert($records);
            $this->commit();

            return count($records);
        } catch (\Exception $e) {
            $this->rollback();
            throw $e;
        }
    }

    /**
     * Get revoked tokens count by date
     */
    public function getCountByDate(Carbon $date): int
    {
        return $this->model
            ->whereDate('created_at', $date)
            ->count();
    }

    /**
     * Get statistics
     */
    public function getStatistics(): array
    {
        return [
            'total' => $this->countRevoked(),
            'active' => $this->countActive(),
            'expired' => $this->countExpired(),
        ];
    }
}
