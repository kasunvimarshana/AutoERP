<?php

declare(strict_types=1);

namespace Modules\User\Services\Platform\Invitations;

use Illuminate\Database\DatabaseManager;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Modules\Core\Contracts\ClockInterface;
use Modules\Core\Contracts\TenantExecutionContextInterface;
use Modules\User\Constants\PlatformOperatorInvitationDeliveryStatus;
use Modules\User\Constants\PlatformOperatorInvitationStatus;
use Modules\User\Constants\PlatformOperatorStatus;
use Modules\User\Models\PlatformOperatorInvitationDeliveryModel;
use Modules\User\Models\PlatformOperatorInvitationModel;
use Modules\User\Models\PlatformOperatorModel;
use Modules\User\Notifications\PlatformOperatorInvitationNotification;
use Psr\Log\LoggerInterface;
use RuntimeException;
use Throwable;

final class PlatformOperatorInvitationDeliveryService
{
    private const DELIVERY_FAILURE_CODE = 'PLATFORM_OPERATOR_INVITATION_DELIVERY_FAILED';
    private const DELIVERY_FAILURE_MESSAGE = 'The invitation email could not be sent. Check the mail transport and retry.';
    private const DEFAULT_LEASE_SECONDS = 300;
    private const INVITATION_EXPIRED_CODE = 'PLATFORM_OPERATOR_INVITATION_EXPIRED';
    private const INVITATION_UNAVAILABLE_CODE = 'PLATFORM_OPERATOR_INVITATION_UNAVAILABLE';

    public function __construct(
        private readonly PlatformOperatorInvitationModel $invitations,
        private readonly PlatformOperatorInvitationDeliveryModel $deliveries,
        private readonly ClockInterface $clock,
        private readonly TenantExecutionContextInterface $executionContext,
        private readonly DatabaseManager $database,
        private readonly LoggerInterface $logger,
    ) {}

    public function deliver(int $invitationId): void
    {
        $claim = null;
        try {
            $claim = $this->executionContext->runAsControlPlane(fn (): ?array => $this->claim($invitationId));
            if (! is_array($claim)) {
                return;
            }
            if (! $this->executionContext->runAsControlPlane(
                fn (): bool => $this->isClaimSendable($claim),
            )) {
                return;
            }
            Notification::route('mail', (string) $claim['email'])->notify(new PlatformOperatorInvitationNotification(
                (string) $claim['name'],
                $this->acceptanceUrl((string) $claim['token']),
                (string) $claim['expires_at'],
            ));
            $updated = $this->executionContext->runAsControlPlane(
                fn (): int => $this->finalizeSent($claim),
            );
            if ($updated !== 1) {
                $this->logger->warning('Platform operator invitation email was handed to the mail transport after its delivery claim changed.', [
                    'invitation_id' => $invitationId,
                    'delivery_id' => $claim['delivery_id'],
                ]);
            }
        } catch (Throwable $exception) {
            $this->logger->error('Platform operator invitation delivery failed.', [
                'invitation_id' => $invitationId,
                'exception' => $exception,
            ]);
            if (is_array($claim)) {
                $this->executionContext->runAsControlPlane(fn (): int => $this->finalizeFailed($claim));
            }
            throw $exception;
        }
    }

    /** @return array<string,mixed>|null */
    private function claim(int $invitationId): ?array
    {
        return $this->database->transaction(function () use ($invitationId): ?array {
            $invitation = $this->invitations->newQuery()->with('operator')->whereKey($invitationId)
                ->lockForUpdate()->first();
            if (! $invitation instanceof PlatformOperatorInvitationModel) {
                return null;
            }
            if ($invitation->getAttribute('status') !== PlatformOperatorInvitationStatus::PENDING) {
                $this->cancelOpenDeliveries(
                    $invitationId,
                    self::INVITATION_UNAVAILABLE_CODE,
                    'The invitation is no longer pending.',
                );

                return null;
            }
            if ($invitation->getAttribute('expires_at')->toImmutable() <= $this->clock->now()) {
                $invitation->forceFill([
                    'status' => PlatformOperatorInvitationStatus::EXPIRED,
                    'delivery_token' => null,
                    'row_version' => (int) $invitation->getAttribute('row_version') + 1,
                ])->save();
                $this->cancelOpenDeliveries(
                    $invitationId,
                    self::INVITATION_EXPIRED_CODE,
                    'The invitation expired before delivery completed.',
                );

                return null;
            }
            $operator = $invitation->operator;
            $token = trim((string) $invitation->getAttribute('delivery_token'));
            if (! $operator instanceof PlatformOperatorModel
                || $operator->getAttribute('status') !== PlatformOperatorStatus::INVITED
                || $token === ''
            ) {
                $this->cancelOpenDeliveries(
                    $invitationId,
                    self::INVITATION_UNAVAILABLE_CODE,
                    'The invitation recipient or delivery token is no longer available.',
                );

                return null;
            }

            $delivery = $this->deliveries->newQuery()->where('invitation_id', $invitationId)
                ->latest('attempt_number')->lockForUpdate()->first();
            if ($delivery instanceof PlatformOperatorInvitationDeliveryModel) {
                if ($delivery->getAttribute('status') === PlatformOperatorInvitationDeliveryStatus::SENT) {
                    return null;
                }
                if ($delivery->getAttribute('status') === PlatformOperatorInvitationDeliveryStatus::SENDING
                    && $delivery->getAttribute('lease_expires_at')?->toImmutable() > $this->clock->now()
                ) {
                    return null;
                }
                if ($delivery->getAttribute('status') === PlatformOperatorInvitationDeliveryStatus::FAILED) {
                    $delivery = $this->deliveries->newQuery()->create([
                        'invitation_id' => $invitationId,
                        'attempt_number' => (int) $delivery->getAttribute('attempt_number') + 1,
                        'status' => PlatformOperatorInvitationDeliveryStatus::QUEUED,
                        'row_version' => 1,
                    ]);
                }
            } else {
                $delivery = $this->deliveries->newQuery()->create([
                    'invitation_id' => $invitationId,
                    'attempt_number' => 1,
                    'status' => PlatformOperatorInvitationDeliveryStatus::QUEUED,
                    'row_version' => 1,
                ]);
            }
            if ($delivery->getAttribute('status') === PlatformOperatorInvitationDeliveryStatus::CANCELLED) {
                return null;
            }

            $claimToken = (string) Str::uuid();
            $claimedAt = $this->clock->now();
            $leaseSeconds = max(30, (int) config(
                'user.platform.operator_invitation_delivery_lease_seconds',
                self::DEFAULT_LEASE_SECONDS,
            ));
            $nextVersion = (int) $delivery->getAttribute('row_version') + 1;
            $delivery->forceFill([
                'status' => PlatformOperatorInvitationDeliveryStatus::SENDING,
                'claim_token' => $claimToken,
                'claimed_at' => $claimedAt,
                'lease_expires_at' => $claimedAt->modify("+{$leaseSeconds} seconds"),
                'failed_at' => null,
                'error_code' => null,
                'error_message' => null,
                'row_version' => $nextVersion,
            ])->save();

            return [
                'delivery_id' => (int) $delivery->getKey(),
                'invitation_id' => (int) $invitation->getKey(),
                'invitation_row_version' => (int) $invitation->getAttribute('row_version'),
                'operator_id' => (int) $operator->getKey(),
                'operator_row_version' => (int) $operator->getAttribute('row_version'),
                'claim_token' => $claimToken,
                'row_version' => $nextVersion,
                'email' => (string) $operator->getAttribute('email'),
                'name' => trim((string) $operator->getAttribute('first_name').' '.(string) $operator->getAttribute('last_name')),
                'token' => $token,
                'expires_at' => $invitation->getAttribute('expires_at')->toAtomString(),
            ];
        }, 3);
    }

    private function cancelOpenDeliveries(int $invitationId, string $errorCode, string $message): void
    {
        $this->deliveries->newQuery()
            ->where('invitation_id', $invitationId)
            ->whereIn('status', [
                PlatformOperatorInvitationDeliveryStatus::QUEUED,
                PlatformOperatorInvitationDeliveryStatus::SENDING,
                PlatformOperatorInvitationDeliveryStatus::FAILED,
            ])
            ->increment('row_version', 1, [
                'status' => PlatformOperatorInvitationDeliveryStatus::CANCELLED,
                'claim_token' => null,
                'claimed_at' => null,
                'lease_expires_at' => null,
                'error_code' => $errorCode,
                'error_message' => $message,
                'updated_at' => $this->clock->now(),
            ]);
    }

    /** @param array<string,mixed> $claim */
    private function isClaimSendable(array $claim): bool
    {
        return $this->deliveries->newQuery()
            ->whereKey((int) $claim['delivery_id'])
            ->where('status', PlatformOperatorInvitationDeliveryStatus::SENDING)
            ->where('claim_token', (string) $claim['claim_token'])
            ->where('row_version', (int) $claim['row_version'])
            ->where('lease_expires_at', '>', $this->clock->now())
            ->whereHas('invitation', function ($query) use ($claim): void {
                $query->whereKey((int) $claim['invitation_id'])
                    ->where('row_version', (int) $claim['invitation_row_version'])
                    ->where('status', PlatformOperatorInvitationStatus::PENDING)
                    ->where('expires_at', '>', $this->clock->now())
                    ->whereNotNull('delivery_token')
                    ->whereHas('operator', function ($operatorQuery) use ($claim): void {
                        $operatorQuery->whereKey((int) $claim['operator_id'])
                            ->where('row_version', (int) $claim['operator_row_version'])
                            ->where('email', (string) $claim['email'])
                            ->where('status', PlatformOperatorStatus::INVITED);
                    });
            })
            ->exists();
    }

    /** @param array<string,mixed> $claim */
    private function finalizeSent(array $claim): int
    {
        return $this->claimQuery($claim)->update([
            'status' => PlatformOperatorInvitationDeliveryStatus::SENT,
            'sent_at' => $this->clock->now(),
            'mail_provider' => (string) config('mail.default', 'unknown'),
            'claim_token' => null,
            'claimed_at' => null,
            'lease_expires_at' => null,
            'error_code' => null,
            'error_message' => null,
            'row_version' => (int) $claim['row_version'] + 1,
            'updated_at' => $this->clock->now(),
        ]);
    }

    /** @param array<string,mixed> $claim */
    private function finalizeFailed(array $claim): int
    {
        return $this->claimQuery($claim)->update([
            'status' => PlatformOperatorInvitationDeliveryStatus::FAILED,
            'failed_at' => $this->clock->now(),
            'claim_token' => null,
            'claimed_at' => null,
            'lease_expires_at' => null,
            'error_code' => self::DELIVERY_FAILURE_CODE,
            'error_message' => self::DELIVERY_FAILURE_MESSAGE,
            'row_version' => (int) $claim['row_version'] + 1,
            'updated_at' => $this->clock->now(),
        ]);
    }

    /** @param array<string,mixed> $claim */
    private function claimQuery(array $claim)
    {
        return $this->deliveries->newQuery()->whereKey((int) $claim['delivery_id'])
            ->where('status', PlatformOperatorInvitationDeliveryStatus::SENDING)
            ->where('claim_token', (string) $claim['claim_token'])
            ->where('row_version', (int) $claim['row_version']);
    }

    private function acceptanceUrl(string $token): string
    {
        $baseUrl = rtrim(trim((string) config('user.platform.operator_invitation_url', '')), '/#');
        if ($baseUrl === '' || filter_var($baseUrl, FILTER_VALIDATE_URL) === false
            || ! in_array(strtolower((string) parse_url($baseUrl, PHP_URL_SCHEME)), ['http', 'https'], true)
        ) {
            throw new RuntimeException('The platform operator invitation URL is not configured correctly.');
        }

        return $baseUrl.'#token='.rawurlencode($token);
    }
}
