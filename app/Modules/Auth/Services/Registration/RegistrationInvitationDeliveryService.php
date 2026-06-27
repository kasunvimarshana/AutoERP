<?php

declare(strict_types=1);

namespace Modules\Auth\Services\Registration;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Modules\Auth\Constants\InvitationDeliveryStatus;
use Modules\Auth\Constants\RegistrationInvitationStatus;
use Modules\Auth\Models\AuthRegistrationInvitationDeliveryModel;
use Modules\Auth\Models\AuthRegistrationInvitationModel;
use Modules\Auth\Notifications\RegistrationInvitationNotification;
use Modules\Core\Contracts\ClockInterface;
use Modules\Core\Contracts\TenantDirectoryInterface;
use Modules\Core\Contracts\TenantExecutionContextInterface;
use Psr\Log\LoggerInterface;
use RuntimeException;
use Throwable;

final class RegistrationInvitationDeliveryService
{
    private const DELIVERY_FAILURE_CODE = 'AUTH_INVITATION_DELIVERY_FAILED';
    private const DELIVERY_FAILURE_MESSAGE = 'The invitation email could not be sent. Check the mail transport and retry the delivery.';
    private const MISSING_TOKEN_CODE = 'AUTH_INVITATION_DELIVERY_TOKEN_MISSING';
    private const DEFAULT_LEASE_SECONDS = 300;

    public function __construct(
        private readonly AuthRegistrationInvitationDeliveryModel $deliveries,
        private readonly TenantExecutionContextInterface $executionContext,
        private readonly TenantDirectoryInterface $tenants,
        private readonly ClockInterface $clock,
        private readonly LoggerInterface $logger,
    ) {}

    public function deliver(int $tenantId, int $deliveryId): void
    {
        $claim = null;

        try {
            $claim = $this->executionContext->runForTenant(
                $tenantId,
                fn (): ?array => $this->claim($tenantId, $deliveryId),
            );

            if (! is_array($claim)) {
                return;
            }

            if (! $this->executionContext->runForTenant(
                $tenantId,
                fn (): bool => $this->isClaimSendable($tenantId, $deliveryId, $claim),
            )) {
                return;
            }

            $registrationUrl = $this->registrationUrl((string) $claim['token']);
            Notification::route('mail', (string) $claim['email'])->notify(
                new RegistrationInvitationNotification(
                    (string) $claim['tenant_name'],
                    $registrationUrl,
                    (string) $claim['expires_at'],
                    (string) $claim['purpose'],
                ),
            );

            $updated = $this->executionContext->runForTenant(
                $tenantId,
                fn (): int => $this->finalizeSent($tenantId, $deliveryId, $claim),
            );

            if ($updated !== 1) {
                $this->logger->warning('Invitation email was handed to the mail transport after its delivery claim changed.', [
                    'tenant_id' => $tenantId,
                    'delivery_id' => $deliveryId,
                    'invitation_id' => $claim['invitation_id'],
                ]);
            }
        } catch (Throwable $exception) {
            $this->logger->error('Registration invitation delivery failed.', [
                'tenant_id' => $tenantId,
                'delivery_id' => $deliveryId,
                'exception' => $exception,
            ]);

            if (is_array($claim)) {
                $this->executionContext->runForTenant(
                    $tenantId,
                    fn (): int => $this->finalizeFailed($tenantId, $deliveryId, $claim),
                );
            }

            throw $exception;
        }
    }

    /** @return array<string, mixed>|null */
    private function claim(int $tenantId, int $deliveryId): ?array
    {
        return DB::transaction(function () use ($tenantId, $deliveryId): ?array {
            $delivery = $this->deliveries->newQuery()
                ->with('invitation')
                ->whereKey($deliveryId)
                ->where('tenant_id', $tenantId)
                ->lockForUpdate()
                ->first();

            if (! $delivery instanceof AuthRegistrationInvitationDeliveryModel) {
                return null;
            }

            $tenant = $this->tenants->summary($tenantId);
            if ($tenant === null) {
                $this->cancelInvalidDelivery($delivery, 'The tenant record is unavailable.');

                return null;
            }

            $status = (string) $delivery->getAttribute('status');
            if (InvitationDeliveryStatus::isTerminal($status)) {
                return null;
            }

            if (
                $status === InvitationDeliveryStatus::SENDING
                && $delivery->getAttribute('lease_expires_at')?->toImmutable() > $this->clock->now()
            ) {
                return null;
            }

            $invitation = $delivery->invitation;
            if (! $invitation instanceof AuthRegistrationInvitationModel) {
                $this->cancelInvalidDelivery($delivery, 'The invitation record is unavailable.');

                return null;
            }

            if (
                $invitation->getAttribute('status') !== RegistrationInvitationStatus::PENDING
                || $invitation->getAttribute('expires_at')->toImmutable() <= $this->clock->now()
            ) {
                $this->cancelInvalidDelivery($delivery, 'The invitation is no longer pending or has expired.');

                return null;
            }

            $token = trim((string) $invitation->getAttribute('delivery_token'));
            if ($token === '') {
                $delivery->forceFill([
                    'status' => InvitationDeliveryStatus::FAILED,
                    'failed_at' => $this->clock->now(),
                    'error_code' => self::MISSING_TOKEN_CODE,
                    'error_message' => 'The invitation delivery token is unavailable. Replace the invitation.',
                    'claim_token' => null,
                    'claimed_at' => null,
                    'lease_expires_at' => null,
                    'row_version' => (int) $delivery->getAttribute('row_version') + 1,
                ])->save();

                return null;
            }

            $claimToken = (string) Str::uuid();
            $claimedAt = $this->clock->now();
            $leaseSeconds = max(30, (int) config(
                'module-auth.registration.delivery_lease_seconds',
                self::DEFAULT_LEASE_SECONDS,
            ));
            $nextVersion = (int) $delivery->getAttribute('row_version') + 1;

            $delivery->forceFill([
                'status' => InvitationDeliveryStatus::SENDING,
                'processing_attempt_count' => (int) $delivery->getAttribute('processing_attempt_count') + 1,
                'claim_token' => $claimToken,
                'claimed_at' => $claimedAt,
                'lease_expires_at' => $claimedAt->modify(sprintf('+%d seconds', $leaseSeconds)),
                'failed_at' => null,
                'error_code' => null,
                'error_message' => null,
                'row_version' => $nextVersion,
            ])->save();

            return [
                'claim_token' => $claimToken,
                'row_version' => $nextVersion,
                'invitation_id' => (int) $invitation->getKey(),
                'invitation_row_version' => (int) $invitation->getAttribute('row_version'),
                'email' => (string) $invitation->getAttribute('email'),
                'tenant_name' => $tenant['name'],
                'token' => $token,
                'expires_at' => $invitation->getAttribute('expires_at')->toAtomString(),
                'purpose' => (string) $invitation->getAttribute('purpose'),
            ];
        }, 3);
    }

    /** @param array<string, mixed> $claim */
    private function isClaimSendable(int $tenantId, int $deliveryId, array $claim): bool
    {
        return $this->deliveries->newQuery()
            ->whereKey($deliveryId)
            ->where('tenant_id', $tenantId)
            ->where('status', InvitationDeliveryStatus::SENDING)
            ->where('claim_token', (string) $claim['claim_token'])
            ->where('row_version', (int) $claim['row_version'])
            ->where('lease_expires_at', '>', $this->clock->now())
            ->whereHas('invitation', function ($query) use ($claim): void {
                $query->whereKey((int) $claim['invitation_id'])
                    ->where('row_version', (int) $claim['invitation_row_version'])
                    ->where('email', (string) $claim['email'])
                    ->where('status', RegistrationInvitationStatus::PENDING)
                    ->where('expires_at', '>', $this->clock->now())
                    ->whereNotNull('delivery_token');
            })
            ->exists();
    }

    /** @param array<string, mixed> $claim */
    private function finalizeSent(int $tenantId, int $deliveryId, array $claim): int
    {
        return $this->deliveries->newQuery()
            ->whereKey($deliveryId)
            ->where('tenant_id', $tenantId)
            ->where('status', InvitationDeliveryStatus::SENDING)
            ->where('claim_token', (string) $claim['claim_token'])
            ->where('row_version', (int) $claim['row_version'])
            ->update([
                'status' => InvitationDeliveryStatus::SENT,
                'sent_at' => $this->clock->now(),
                'provider' => (string) config('mail.default', 'unknown'),
                'claim_token' => null,
                'claimed_at' => null,
                'lease_expires_at' => null,
                'error_code' => null,
                'error_message' => null,
                'row_version' => (int) $claim['row_version'] + 1,
                'updated_at' => $this->clock->now(),
            ]);
    }

    /** @param array<string, mixed> $claim */
    private function finalizeFailed(int $tenantId, int $deliveryId, array $claim): int
    {
        return $this->deliveries->newQuery()
            ->whereKey($deliveryId)
            ->where('tenant_id', $tenantId)
            ->where('status', InvitationDeliveryStatus::SENDING)
            ->where('claim_token', (string) $claim['claim_token'])
            ->where('row_version', (int) $claim['row_version'])
            ->update([
                'status' => InvitationDeliveryStatus::FAILED,
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

    private function cancelInvalidDelivery(
        AuthRegistrationInvitationDeliveryModel $delivery,
        string $message,
    ): void {
        $delivery->forceFill([
            'status' => InvitationDeliveryStatus::CANCELLED,
            'cancelled_at' => $this->clock->now(),
            'claim_token' => null,
            'claimed_at' => null,
            'lease_expires_at' => null,
            'error_code' => 'AUTH_INVITATION_CANCELLED',
            'error_message' => $message,
            'row_version' => (int) $delivery->getAttribute('row_version') + 1,
        ])->save();
    }

    private function registrationUrl(string $token): string
    {
        $baseUrl = rtrim(trim((string) config('module-auth.registration.invitation_url', '')), '/#');
        if (
            $baseUrl === ''
            || filter_var($baseUrl, FILTER_VALIDATE_URL) === false
            || ! in_array(strtolower((string) parse_url($baseUrl, PHP_URL_SCHEME)), ['http', 'https'], true)
        ) {
            throw new RuntimeException('The tenant registration invitation URL is not configured correctly.');
        }

        return $baseUrl.'#token='.rawurlencode($token);
    }
}
