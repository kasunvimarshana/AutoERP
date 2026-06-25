<?php

declare(strict_types=1);

namespace Modules\Tenant\Services\Onboarding;

use DateInterval;
use Illuminate\Support\Facades\DB;
use Modules\Core\Contracts\ClockInterface;
use Modules\Core\Contracts\CurrentUserContextAccessorInterface;
use Modules\Tenant\Constants\TenantOnboardingErrorCode;
use Modules\Tenant\Constants\TenantOnboardingStatus;
use Modules\Tenant\Constants\TenantOnboardingStep;
use Modules\Tenant\Constants\TenantOnboardingStepStatus;
use Modules\Tenant\Exceptions\TenantOnboardingOperationException;
use Modules\Tenant\Models\TenantOnboardingStateModel;
use Modules\Tenant\Models\TenantOnboardingStepModel;

final class TenantOnboardingProgressService
{
    private const DEFAULT_LEASE_MINUTES = 15;

    public function __construct(
        private readonly TenantOnboardingStateModel $states,
        private readonly TenantOnboardingStepModel $steps,
        private readonly ClockInterface $clock,
        private readonly CurrentUserContextAccessorInterface $currentUser,
    ) {}

    public function begin(int $tenantId, string $email, string $operationId, string $correlationId): TenantOnboardingStateModel
    {
        return DB::transaction(function () use ($tenantId, $email, $operationId, $correlationId): TenantOnboardingStateModel {
            $state = $this->states->newQuery()->where('tenant_id', $tenantId)->lockForUpdate()->first();
            if (! $state instanceof TenantOnboardingStateModel) {
                $state = $this->states->newQuery()->create([
                    'tenant_id' => $tenantId,
                    'status' => TenantOnboardingStatus::PENDING,
                    'row_version' => 1,
                    'created_by' => $this->currentUser->currentUserId(),
                    'updated_by' => $this->currentUser->currentUserId(),
                ]);
            }

            $now = $this->clock->now();
            $leaseExpiresAt = $state->getAttribute('operation_lease_expires_at');
            $activeOperation = trim((string) $state->getAttribute('operation_id'));
            if (
                $state->getAttribute('status') === TenantOnboardingStatus::PROVISIONING
                && $activeOperation !== ''
                && $activeOperation !== $operationId
                && $leaseExpiresAt !== null
                && $leaseExpiresAt->toImmutable() > $now
            ) {
                throw new TenantOnboardingOperationException(
                    TenantOnboardingErrorCode::OPERATION_IN_PROGRESS,
                    'Tenant foundation provisioning is already in progress.',
                    correlationId: (string) ($state->getAttribute('correlation_id') ?: $correlationId),
                );
            }

            $existingEmail = strtolower(trim((string) $state->getAttribute('initial_admin_email')));
            if (
                is_numeric($state->getAttribute('invitation_id'))
                && $existingEmail !== ''
                && $existingEmail !== $email
            ) {
                throw new TenantOnboardingOperationException(
                    TenantOnboardingErrorCode::EMAIL_CONFLICT,
                    'A different initial administrator invitation already exists. Revoke or replace it before changing the email.',
                    TenantOnboardingStep::INITIAL_ADMIN_INVITATION,
                    $correlationId,
                );
            }

            foreach (TenantOnboardingStep::ordered() as $step) {
                $this->steps->newQuery()->firstOrCreate(
                    ['tenant_id' => $tenantId, 'step' => $step],
                    [
                        'owner_module' => TenantOnboardingStep::owner($step),
                        'status' => TenantOnboardingStepStatus::PENDING,
                        'attempt_count' => 0,
                    ],
                );
            }

            $state->forceFill([
                'status' => TenantOnboardingStatus::PROVISIONING,
                'operation_id' => $operationId,
                'operation_started_at' => $now,
                'operation_lease_expires_at' => $this->leaseExpiry($now),
                'failed_step' => null,
                'last_error_code' => null,
                'last_error_message' => null,
                'correlation_id' => $correlationId,
                'row_version' => max(1, (int) $state->getAttribute('row_version')) + 1,
                'updated_by' => $this->currentUser->currentUserId(),
            ])->save();

            return $state->fresh() ?? $state;
        }, 3);
    }

    public function startStep(int $tenantId, string $step, string $operationId, string $correlationId): void
    {
        DB::transaction(function () use ($tenantId, $step, $operationId, $correlationId): void {
            $state = $this->lockOwnedState($tenantId, $operationId);
            $record = $this->steps->newQuery()
                ->where('tenant_id', $tenantId)
                ->where('step', $step)
                ->lockForUpdate()
                ->firstOrFail();

            if ($record->getAttribute('status') === TenantOnboardingStepStatus::COMPLETED) {
                return;
            }

            $record->forceFill([
                'status' => TenantOnboardingStepStatus::RUNNING,
                'attempt_count' => (int) $record->getAttribute('attempt_count') + 1,
                'operation_id' => $operationId,
                'started_at' => $this->clock->now(),
                'completed_at' => null,
                'error_code' => null,
                'error_message' => null,
                'correlation_id' => $correlationId,
            ])->save();

            $state->forceFill([
                'operation_lease_expires_at' => $this->leaseExpiry($this->clock->now()),
                'failed_step' => null,
                'last_error_code' => null,
                'last_error_message' => null,
                'row_version' => (int) $state->getAttribute('row_version') + 1,
                'updated_by' => $this->currentUser->currentUserId(),
            ])->save();
        }, 3);
    }

    /** @param array<string, int|string|null> $stateAttributes */
    public function completeStep(
        int $tenantId,
        string $step,
        string $operationId,
        array $stateAttributes = [],
    ): void {
        DB::transaction(function () use ($tenantId, $step, $operationId, $stateAttributes): void {
            $state = $this->lockOwnedState($tenantId, $operationId);
            $record = $this->steps->newQuery()
                ->where('tenant_id', $tenantId)
                ->where('step', $step)
                ->lockForUpdate()
                ->firstOrFail();

            $record->forceFill([
                'status' => TenantOnboardingStepStatus::COMPLETED,
                'operation_id' => $operationId,
                'completed_at' => $this->clock->now(),
                'error_code' => null,
                'error_message' => null,
            ])->save();

            $completed = array_values(array_unique([
                ...$this->completedSteps($state),
                $step,
            ]));
            $completed = array_values(array_filter(
                TenantOnboardingStep::ordered(),
                static fn (string $candidate): bool => in_array($candidate, $completed, true),
            ));

            $state->forceFill([
                ...$stateAttributes,
                'completed_steps' => $completed,
                'operation_lease_expires_at' => $this->leaseExpiry($this->clock->now()),
                'row_version' => (int) $state->getAttribute('row_version') + 1,
                'updated_by' => $this->currentUser->currentUserId(),
            ])->save();
        }, 3);
    }

    public function failStep(
        int $tenantId,
        string $step,
        string $operationId,
        string $correlationId,
        string $errorCode,
        string $safeMessage,
    ): void {
        DB::transaction(function () use (
            $tenantId,
            $step,
            $operationId,
            $correlationId,
            $errorCode,
            $safeMessage,
        ): void {
            $state = $this->states->newQuery()->where('tenant_id', $tenantId)->lockForUpdate()->first();
            if (! $state instanceof TenantOnboardingStateModel) {
                return;
            }

            $this->steps->newQuery()
                ->where('tenant_id', $tenantId)
                ->where('step', $step)
                ->update([
                    'status' => TenantOnboardingStepStatus::FAILED,
                    'operation_id' => $operationId,
                    'completed_at' => null,
                    'error_code' => $errorCode,
                    'error_message' => $safeMessage,
                    'correlation_id' => $correlationId,
                    'updated_at' => $this->clock->now(),
                ]);

            $state->forceFill([
                'status' => TenantOnboardingStatus::FAILED,
                'operation_id' => null,
                'operation_lease_expires_at' => null,
                'failed_step' => $step,
                'last_error_code' => $errorCode,
                'last_error_message' => $safeMessage,
                'correlation_id' => $correlationId,
                'row_version' => (int) $state->getAttribute('row_version') + 1,
                'updated_by' => $this->currentUser->currentUserId(),
            ])->save();
        }, 3);
    }

    public function finishFoundation(int $tenantId, string $operationId): TenantOnboardingStateModel
    {
        return DB::transaction(function () use ($tenantId, $operationId): TenantOnboardingStateModel {
            $state = $this->lockOwnedState($tenantId, $operationId);
            $state->forceFill([
                'status' => TenantOnboardingStatus::AWAITING_ADMINISTRATOR,
                'operation_id' => null,
                'operation_lease_expires_at' => null,
                'failed_step' => null,
                'last_error_code' => null,
                'last_error_message' => null,
                'provisioned_at' => $state->getAttribute('provisioned_at') ?? $this->clock->now(),
                'row_version' => (int) $state->getAttribute('row_version') + 1,
                'updated_by' => $this->currentUser->currentUserId(),
            ])->save();

            return $state->fresh(['steps']) ?? $state;
        }, 3);
    }

    /** @param array<string, mixed> $invitation */
    public function replaceInvitationReference(
        int $tenantId,
        int $expectedStateVersion,
        string $email,
        array $invitation,
    ): TenantOnboardingStateModel {
        return DB::transaction(function () use (
            $tenantId,
            $expectedStateVersion,
            $email,
            $invitation,
        ): TenantOnboardingStateModel {
            $state = $this->states->newQuery()->where('tenant_id', $tenantId)->lockForUpdate()->firstOrFail();
            if ((int) $state->getAttribute('row_version') !== $expectedStateVersion) {
                throw new TenantOnboardingOperationException(
                    TenantOnboardingErrorCode::VERSION_CONFLICT,
                    'The onboarding state changed after it was loaded. Refresh and try again.',
                    TenantOnboardingStep::INITIAL_ADMIN_INVITATION,
                    (string) $state->getAttribute('correlation_id'),
                );
            }

            $step = $this->steps->newQuery()
                ->where('tenant_id', $tenantId)
                ->where('step', TenantOnboardingStep::INITIAL_ADMIN_INVITATION)
                ->lockForUpdate()
                ->firstOrFail();
            $step->forceFill([
                'status' => TenantOnboardingStepStatus::COMPLETED,
                'attempt_count' => (int) $step->getAttribute('attempt_count') + 1,
                'completed_at' => $this->clock->now(),
                'error_code' => null,
                'error_message' => null,
            ])->save();

            $completed = array_values(array_unique([
                ...$this->completedSteps($state),
                TenantOnboardingStep::INITIAL_ADMIN_INVITATION,
            ]));
            $completed = array_values(array_filter(
                TenantOnboardingStep::ordered(),
                static fn (string $candidate): bool => in_array($candidate, $completed, true),
            ));

            $state->forceFill([
                'status' => TenantOnboardingStatus::AWAITING_ADMINISTRATOR,
                'initial_admin_email' => strtolower(trim($email)),
                'invitation_id' => (int) ($invitation['invitation_id'] ?? 0),
                'completed_steps' => $completed,
                'failed_step' => null,
                'last_error_code' => null,
                'last_error_message' => null,
                'row_version' => $expectedStateVersion + 1,
                'updated_by' => $this->currentUser->currentUserId(),
            ])->save();

            return $state->fresh(['steps']) ?? $state;
        }, 3);
    }

    public function clearInvitationReference(
        int $tenantId,
        int $expectedStateVersion,
    ): TenantOnboardingStateModel {
        return DB::transaction(function () use ($tenantId, $expectedStateVersion): TenantOnboardingStateModel {
            $state = $this->states->newQuery()->where('tenant_id', $tenantId)->lockForUpdate()->firstOrFail();
            if ((int) $state->getAttribute('row_version') !== $expectedStateVersion) {
                throw new TenantOnboardingOperationException(
                    TenantOnboardingErrorCode::VERSION_CONFLICT,
                    'The onboarding state changed after it was loaded. Refresh and try again.',
                    TenantOnboardingStep::INITIAL_ADMIN_INVITATION,
                    (string) $state->getAttribute('correlation_id'),
                );
            }

            $this->steps->newQuery()
                ->where('tenant_id', $tenantId)
                ->where('step', TenantOnboardingStep::INITIAL_ADMIN_INVITATION)
                ->update([
                    'status' => TenantOnboardingStepStatus::PENDING,
                    'operation_id' => null,
                    'started_at' => null,
                    'completed_at' => null,
                    'error_code' => null,
                    'error_message' => null,
                    'correlation_id' => null,
                    'updated_at' => $this->clock->now(),
                ]);

            $completed = array_values(array_filter(
                $this->completedSteps($state),
                static fn (string $step): bool => $step !== TenantOnboardingStep::INITIAL_ADMIN_INVITATION,
            ));
            $state->forceFill([
                'status' => TenantOnboardingStatus::AWAITING_ADMINISTRATOR,
                'initial_admin_email' => null,
                'invitation_id' => null,
                'completed_steps' => $completed,
                'failed_step' => null,
                'last_error_code' => null,
                'last_error_message' => null,
                'row_version' => $expectedStateVersion + 1,
                'updated_by' => $this->currentUser->currentUserId(),
            ])->save();

            return $state->fresh(['steps']) ?? $state;
        }, 3);
    }

    public function snapshot(int $tenantId): ?array
    {
        $state = $this->states->newQuery()->with('steps')->where('tenant_id', $tenantId)->first();

        return $state instanceof TenantOnboardingStateModel ? $this->serialize($state) : null;
    }

    /** @return array<string, mixed> */
    public function serialize(TenantOnboardingStateModel $state): array
    {
        return [
            ...$state->only([
                'status',
                'initial_admin_email',
                'root_organization_unit_id',
                'super_admin_role_id',
                'invitation_id',
                'completed_steps',
                'failed_step',
                'last_error_code',
                'last_error_message',
                'correlation_id',
                'provisioned_at',
                'completed_at',
                'row_version',
            ]),
            'steps' => $state->steps->map(static fn (TenantOnboardingStepModel $step): array => $step->only([
                'step',
                'owner_module',
                'status',
                'attempt_count',
                'started_at',
                'completed_at',
                'error_code',
                'error_message',
                'correlation_id',
            ]))->values()->all(),
        ];
    }

    private function lockOwnedState(int $tenantId, string $operationId): TenantOnboardingStateModel
    {
        $state = $this->states->newQuery()->where('tenant_id', $tenantId)->lockForUpdate()->firstOrFail();
        if ((string) $state->getAttribute('operation_id') !== $operationId) {
            throw new TenantOnboardingOperationException(
                TenantOnboardingErrorCode::OPERATION_IN_PROGRESS,
                'Tenant foundation provisioning ownership changed. Refresh and retry.',
                correlationId: (string) $state->getAttribute('correlation_id'),
            );
        }

        return $state;
    }

    /** @return list<string> */
    private function completedSteps(TenantOnboardingStateModel $state): array
    {
        $value = $state->getAttribute('completed_steps');

        return is_array($value)
            ? array_values(array_filter($value, static fn (mixed $step): bool => is_string($step)))
            : [];
    }

    private function leaseExpiry(\DateTimeImmutable $now): \DateTimeImmutable
    {
        $minutes = max(1, (int) config('tenant.onboarding.operation_lease_minutes', self::DEFAULT_LEASE_MINUTES));

        return $now->add(new DateInterval("PT{$minutes}M"));
    }
}
