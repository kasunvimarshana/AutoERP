<?php

declare(strict_types=1);

namespace Modules\Auth\Infrastructure\Services;

use DateInterval;
use DateTimeImmutable;
use Illuminate\Support\Str;
use Modules\Auth\Application\Contracts\Providers\VerificationProviderInterface;
use Modules\Auth\Application\DTOs\VerificationChallengeRequestData;
use Modules\Auth\Application\DTOs\VerificationChallengeVerifyData;
use Modules\Auth\Application\Repositories\AuthVerificationChallengeRepositoryInterface;
use Modules\Core\Application\Contracts\ClockInterface;
use Modules\Core\Application\Contracts\PasswordHasherInterface;

final class DatabaseVerificationProvider implements VerificationProviderInterface
{
    public function __construct(
        private readonly AuthVerificationChallengeRepositoryInterface $challenges,
        private readonly PasswordHasherInterface $passwordHasher,
        private readonly ClockInterface $clock,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function requestChallenge(VerificationChallengeRequestData $data): array
    {
        $challengeKey = Str::random(40);
        $challengeSecret = (string) random_int(100000, 999999);

        $issuedAt = $this->clock->now();
        $record = $this->challenges->create([
            'tenant_id' => $data->tenantId,
            'organization_unit_id' => $data->organizationUnitId,
            'provider_id' => $data->providerId,
            'identity_id' => $data->identityId,
            'user_id' => $data->userId,
            'challenge_key' => $challengeKey,
            'challenge_type' => $data->challengeType,
            'channel' => $data->channel,
            'target' => $data->target,
            'challenge_hash' => $this->passwordHasher->hash($challengeSecret),
            'attempts' => 0,
            'max_attempts' => 5,
            'status' => 'pending',
            'issued_at' => $issuedAt,
            'expires_at' => $issuedAt->add(new DateInterval('PT' . $data->ttlSeconds . 'S')),
            'row_version' => 1,
            'metadata' => $data->metadata,
        ]);

        return [
            'challenge_id' => $record->id(),
            'challenge_key' => $challengeKey,
            'channel' => $data->channel,
            'target' => $this->maskTarget($data->target),
            'expires_at' => $record->get('expires_at'),
        ];
    }

    public function verifyChallenge(VerificationChallengeVerifyData $data): bool
    {
        $record = $this->challenges->findActiveByChallengeKey($data->tenantId, $data->challengeKey);
        if ($record === null) {
            return false;
        }

        $expiresAt = $record->get('expires_at');
        if ($expiresAt !== null && $this->clock->now() > new DateTimeImmutable((string) $expiresAt)) {
            $this->challenges->update($record->id(), [
                'status' => 'expired',
                'row_version' => ((int) $record->get('row_version', 1)) + 1,
            ]);

            return false;
        }

        $attempts = (int) $record->get('attempts', 0);
        $maxAttempts = (int) $record->get('max_attempts', 5);
        if ($attempts >= $maxAttempts) {
            $this->challenges->update($record->id(), [
                'status' => 'failed',
            'revoked_at' => $this->clock->now(),
            ]);

            return false;
        }

        $valid = $this->passwordHasher->verify($data->challengeSecret, (string) $record->get('challenge_hash', ''));
        if (! $valid) {
            $this->challenges->update($record->id(), [
                'attempts' => $attempts + 1,
                'row_version' => ((int) $record->get('row_version', 1)) + 1,
            ]);

            return false;
        }

        $this->challenges->update($record->id(), [
            'status' => 'verified',
            'verified_at' => $this->clock->now(),
            'row_version' => ((int) $record->get('row_version', 1)) + 1,
        ]);

        return true;
    }

    private function maskTarget(string $target): string
    {
        $normalized = trim($target);
        if ($normalized === '') {
            return '';
        }

        $length = strlen($normalized);
        if ($length <= 4) {
            return str_repeat('*', $length);
        }

        return substr($normalized, 0, 2) . str_repeat('*', $length - 4) . substr($normalized, -2);
    }
}
