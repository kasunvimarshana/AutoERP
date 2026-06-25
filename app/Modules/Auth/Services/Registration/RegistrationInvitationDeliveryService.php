<?php

declare(strict_types=1);

namespace Modules\Auth\Services\Registration;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Modules\Auth\Constants\InvitationDeliveryStatus;
use Modules\Auth\Constants\RegistrationInvitationStatus;
use Modules\Auth\Models\AuthRegistrationInvitationModel;
use Modules\Auth\Notifications\InitialAdministratorInvitationNotification;
use Modules\Core\Contracts\ClockInterface;
use Modules\Core\Contracts\TenantExecutionContextInterface;
use Psr\Log\LoggerInterface;
use Throwable;

final class RegistrationInvitationDeliveryService
{
    private const DELIVERY_FAILURE_CODE = 'AUTH_INVITATION_DELIVERY_FAILED';
    private const DELIVERY_FAILURE_MESSAGE = 'The invitation email could not be delivered. Retry the delivery after checking the mail service.';

    public function __construct(
        private readonly AuthRegistrationInvitationModel $invitations,
        private readonly TenantExecutionContextInterface $executionContext,
        private readonly ClockInterface $clock,
        private readonly LoggerInterface $logger,
    ) {}

    public function deliver(int $tenantId, int $invitationId): void
    {
        try {
            $payload = $this->executionContext->runForTenant(
                $tenantId,
                fn (): ?array => DB::transaction(function () use ($invitationId): ?array {
                    $invitation = $this->invitations->newQuery()
                        ->with('tenant:id,name')
                        ->whereKey($invitationId)
                        ->where('status', RegistrationInvitationStatus::PENDING)
                        ->where('expires_at', '>', $this->clock->now())
                        ->lockForUpdate()
                        ->first();

                    if (! $invitation instanceof AuthRegistrationInvitationModel) {
                        return null;
                    }

                    $token = trim((string) $invitation->getAttribute('delivery_token'));
                    if ($token === '') {
                        return null;
                    }

                    $invitation->forceFill([
                        'delivery_attempt_count' => (int) $invitation->getAttribute('delivery_attempt_count') + 1,
                        'delivery_requested_at' => $this->clock->now(),
                        'delivery_error_code' => null,
                        'delivery_error_message' => null,
                        'row_version' => (int) $invitation->getAttribute('row_version') + 1,
                    ])->save();

                    return [
                        'email' => (string) $invitation->getAttribute('email'),
                        'tenant_name' => (string) ($invitation->tenant?->getAttribute('name') ?? 'your organization'),
                        'token' => $token,
                        'expires_at' => $invitation->getAttribute('expires_at')->toAtomString(),
                    ];
                }, 3),
            );

            if (! is_array($payload)) {
                return;
            }

            $baseUrl = rtrim((string) config(
                'module-auth.registration.invitation_url',
                rtrim((string) config('app.url'), '/').'/register/invitation',
            ), '#');
            $registrationUrl = $baseUrl.'#token='.rawurlencode((string) $payload['token']);

            Notification::route('mail', (string) $payload['email'])->notify(
                new InitialAdministratorInvitationNotification(
                    (string) $payload['tenant_name'],
                    $registrationUrl,
                    (string) $payload['expires_at'],
                ),
            );

            $this->executionContext->runForTenant($tenantId, function () use ($invitationId): void {
                $this->invitations->newQuery()
                    ->whereKey($invitationId)
                    ->where('status', RegistrationInvitationStatus::PENDING)
                    ->update([
                        'delivery_status' => InvitationDeliveryStatus::SENT,
                        'delivered_at' => $this->clock->now(),
                        'delivery_error_code' => null,
                        'delivery_error_message' => null,
                        'row_version' => DB::raw('row_version + 1'),
                        'updated_at' => $this->clock->now(),
                    ]);
            });
        } catch (Throwable $exception) {
            $this->logger->error('Initial administrator invitation delivery failed.', [
                'tenant_id' => $tenantId,
                'invitation_id' => $invitationId,
                'exception' => $exception,
            ]);

            $this->executionContext->runForTenant($tenantId, function () use ($invitationId): void {
                $this->invitations->newQuery()
                    ->whereKey($invitationId)
                    ->where('status', RegistrationInvitationStatus::PENDING)
                    ->update([
                        'delivery_status' => InvitationDeliveryStatus::FAILED,
                        'delivery_error_code' => self::DELIVERY_FAILURE_CODE,
                        'delivery_error_message' => self::DELIVERY_FAILURE_MESSAGE,
                        'row_version' => DB::raw('row_version + 1'),
                        'updated_at' => $this->clock->now(),
                    ]);
            });

            throw $exception;
        }
    }
}
