<?php

declare(strict_types=1);

namespace Modules\User\Services\Platform\Invitations;

use Illuminate\Database\DatabaseManager;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Modules\Core\Contracts\ClockInterface;
use Modules\Core\Contracts\TenantExecutionContextInterface;
use Modules\User\Constants\PlatformOperatorInvitationDeliveryStatus;
use Modules\User\Constants\PlatformOperatorInvitationStatus;
use Modules\User\Constants\UserStatus;
use Modules\User\Models\PlatformOperatorInvitationModel;
use Modules\User\Models\UserModel;
use Modules\User\Notifications\PlatformOperatorInvitationNotification;
use Psr\Log\LoggerInterface;
use RuntimeException;
use Throwable;

final class PlatformOperatorInvitationDeliveryService
{
    public function __construct(
        private readonly PlatformOperatorInvitationModel $invitations,
        private readonly ClockInterface $clock,
        private readonly TenantExecutionContextInterface $executionContext,
        private readonly DatabaseManager $database,
        private readonly LoggerInterface $logger,
    ) {}

    public function deliver(int $invitationId): void
    {
        $claim = null;

        try {
            $claim = $this->executionContext->runAsControlPlane(
                fn (): ?array => $this->claim($invitationId),
            );
            if (! is_array($claim)) {
                return;
            }

            if (! $this->executionContext->runAsControlPlane(
                fn (): bool => $this->claimIsSendable($invitationId, $claim),
            )) {
                return;
            }

            Notification::route('mail', (string) $claim['email'])->notify(
                new PlatformOperatorInvitationNotification(
                    (string) $claim['name'],
                    $this->acceptanceUrl((string) $claim['token']),
                    (string) $claim['expires_at'],
                ),
            );

            $this->executionContext->runAsControlPlane(
                fn (): int => $this->finalizeSent($invitationId, $claim),
            );
        } catch (Throwable $exception) {
            $this->logger->error('Platform operator invitation delivery failed.', [
                'invitation_id' => $invitationId,
                'exception' => $exception,
            ]);

            if (is_array($claim)) {
                $this->executionContext->runAsControlPlane(
                    fn (): int => $this->finalizeFailed($invitationId, $claim),
                );
            }

            throw $exception;
        }
    }

    /** @return array<string,mixed>|null */
    private function claim(int $invitationId): ?array
    {
        return $this->database->transaction(function () use ($invitationId): ?array {
            $invitation = $this->invitations->newQuery()
                ->with('operator:id,first_name,last_name,platform_login_email,status')
                ->whereKey($invitationId)
                ->lockForUpdate()
                ->first();
            if (! $invitation instanceof PlatformOperatorInvitationModel) {
                return null;
            }

            if ((string) $invitation->getAttribute('status') !== PlatformOperatorInvitationStatus::PENDING) {
                $this->cancel($invitation, 'The invitation is no longer pending.');

                return null;
            }
            if ($invitation->getAttribute('expires_at')->toImmutable() <= $this->clock->now()) {
                $invitation->forceFill([
                    'status' => PlatformOperatorInvitationStatus::EXPIRED,
                    'delivery_status' => PlatformOperatorInvitationDeliveryStatus::CANCELLED,
                    'delivery_token' => null,
                    'claim_token' => null,
                    'claimed_at' => null,
                    'lease_expires_at' => null,
                    'row_version' => (int) $invitation->getAttribute('row_version') + 1,
                ])->save();

                return null;
            }

            $deliveryStatus = (string) $invitation->getAttribute('delivery_status');
            if ($deliveryStatus === PlatformOperatorInvitationDeliveryStatus::SENT) {
                return null;
            }
            if (
                $deliveryStatus === PlatformOperatorInvitationDeliveryStatus::SENDING
                && $invitation->getAttribute('lease_expires_at')?->toImmutable() > $this->clock->now()
            ) {
                return null;
            }

            $operator = $invitation->operator;
            $token = trim((string) $invitation->getAttribute('delivery_token'));
            if (! $operator instanceof UserModel || $operator->getAttribute('status') !== UserStatus::INVITED || $token === '') {
                $this->cancel($invitation, 'The operator or secure delivery token is unavailable.');

                return null;
            }

            $claimToken = (string) Str::uuid();
            $claimedAt = $this->clock->now();
            $leaseSeconds = max(30, (int) config('user.platform.operator_invitation_delivery_lease_seconds', 300));
            $nextVersion = (int) $invitation->getAttribute('row_version') + 1;
            $invitation->forceFill([
                'delivery_status' => PlatformOperatorInvitationDeliveryStatus::SENDING,
                'processing_attempt_count' => (int) $invitation->getAttribute('processing_attempt_count') + 1,
                'claim_token' => $claimToken,
                'claimed_at' => $claimedAt,
                'lease_expires_at' => $claimedAt->modify("+{$leaseSeconds} seconds"),
                'failed_at' => null,
                'error_code' => null,
                'error_message' => null,
                'row_version' => $nextVersion,
            ])->save();

            return [
                'claim_token' => $claimToken,
                'row_version' => $nextVersion,
                'email' => (string) $operator->getAttribute('platform_login_email'),
                'name' => trim((string) $operator->getAttribute('first_name').' '.(string) $operator->getAttribute('last_name')),
                'token' => $token,
                'expires_at' => $invitation->getAttribute('expires_at')->toAtomString(),
            ];
        }, 3);
    }

    /** @param array<string,mixed> $claim */
    private function claimIsSendable(int $invitationId, array $claim): bool
    {
        return $this->invitations->newQuery()
            ->whereKey($invitationId)
            ->where('status', PlatformOperatorInvitationStatus::PENDING)
            ->where('delivery_status', PlatformOperatorInvitationDeliveryStatus::SENDING)
            ->where('claim_token', (string) $claim['claim_token'])
            ->where('row_version', (int) $claim['row_version'])
            ->where('expires_at', '>', $this->clock->now())
            ->whereHas('operator', fn ($query) => $query->where('status', UserStatus::INVITED))
            ->exists();
    }

    /** @param array<string,mixed> $claim */
    private function finalizeSent(int $invitationId, array $claim): int
    {
        return $this->claimQuery($invitationId, $claim)->update([
            'delivery_status' => PlatformOperatorInvitationDeliveryStatus::SENT,
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
    private function finalizeFailed(int $invitationId, array $claim): int
    {
        return $this->claimQuery($invitationId, $claim)->update([
            'delivery_status' => PlatformOperatorInvitationDeliveryStatus::FAILED,
            'failed_at' => $this->clock->now(),
            'claim_token' => null,
            'claimed_at' => null,
            'lease_expires_at' => null,
            'error_code' => 'PLATFORM_OPERATOR_INVITATION_DELIVERY_FAILED',
            'error_message' => 'The invitation email could not be sent. Check the mail transport and retry.',
            'row_version' => (int) $claim['row_version'] + 1,
            'updated_at' => $this->clock->now(),
        ]);
    }

    /** @param array<string,mixed> $claim */
    private function claimQuery(int $invitationId, array $claim): Builder
    {
        return $this->invitations->newQuery()
            ->whereKey($invitationId)
            ->where('status', PlatformOperatorInvitationStatus::PENDING)
            ->where('delivery_status', PlatformOperatorInvitationDeliveryStatus::SENDING)
            ->where('claim_token', (string) $claim['claim_token'])
            ->where('row_version', (int) $claim['row_version']);
    }

    private function cancel(PlatformOperatorInvitationModel $invitation, string $message): void
    {
        $invitation->forceFill([
            'delivery_status' => PlatformOperatorInvitationDeliveryStatus::CANCELLED,
            'claim_token' => null,
            'claimed_at' => null,
            'lease_expires_at' => null,
            'error_code' => 'PLATFORM_OPERATOR_INVITATION_CANCELLED',
            'error_message' => $message,
            'row_version' => (int) $invitation->getAttribute('row_version') + 1,
        ])->save();
    }

    private function acceptanceUrl(string $token): string
    {
        $baseUrl = rtrim(trim((string) config('user.platform.operator_invitation_url', '')), '/#');
        if (
            $baseUrl === ''
            || filter_var($baseUrl, FILTER_VALIDATE_URL) === false
            || ! in_array(strtolower((string) parse_url($baseUrl, PHP_URL_SCHEME)), ['http', 'https'], true)
        ) {
            throw new RuntimeException('The platform operator invitation URL is not configured correctly.');
        }

        return $baseUrl.'#token='.rawurlencode($token);
    }
}
