<?php

declare(strict_types=1);

namespace Tests\Feature\User;

use Illuminate\Database\DatabaseManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Illuminate\Validation\ValidationException;
use Modules\Audit\Contracts\AuditRecorderInterface;
use Modules\Core\Contracts\ClockInterface;
use Modules\Core\Contracts\CurrentUserContextAccessorInterface;
use Modules\Core\Contracts\TenantExecutionContextInterface;
use Modules\User\Constants\PlatformOperatorInvitationDeliveryStatus;
use Modules\User\Constants\PlatformOperatorInvitationStatus;
use Modules\User\Constants\PlatformOperatorStatus;
use Modules\User\Contracts\PlatformOperatorCredentialProvisionerInterface;
use Modules\User\Jobs\DeliverPlatformOperatorInvitation;
use Modules\User\Models\PlatformOperatorInvitationDeliveryModel;
use Modules\User\Models\PlatformOperatorInvitationModel;
use Modules\User\Models\PlatformOperatorModel;
use Modules\User\Services\Platform\Invitations\PlatformOperatorInvitationService;
use Modules\User\Services\Platform\Invitations\PlatformOperatorInvitationTokenCodec;
use Tests\TestCase;

final class PlatformOperatorInvitationResendLifecycleTest extends TestCase
{
    use RefreshDatabase;

    public function test_resend_preserves_a_current_active_token_and_queues_a_new_attempt(): void
    {
        Queue::fake();
        config()->set('user.platform.operator_invitation_ttl_minutes', 60);

        $executionContext = $this->app->make(TenantExecutionContextInterface::class);
        $service = $this->service();
        $operator = $this->createOperator($executionContext);
        $issued = $executionContext->runAsControlPlane(
            fn (): PlatformOperatorInvitationModel => $service->issueForOperator($operator),
        );
        $originalId = (int) $issued->getKey();
        $originalHash = (string) $issued->getAttribute('token_hash');
        $originalToken = (string) $issued->getAttribute('delivery_token');
        $originalExpiry = $issued->getAttribute('expires_at')->toImmutable();

        $this->travel(5)->minutes();
        $updatedOperator = $service->resend((int) $operator->getKey(), 1);

        $reloaded = $executionContext->runAsControlPlane(
            fn (): PlatformOperatorInvitationModel => PlatformOperatorInvitationModel::query()
                ->where('platform_operator_id', $operator->getKey())
                ->where('status', PlatformOperatorInvitationStatus::PENDING)
                ->sole(),
        );
        $deliveries = $executionContext->runAsControlPlane(
            fn () => PlatformOperatorInvitationDeliveryModel::query()
                ->where('invitation_id', $originalId)
                ->orderBy('attempt_number')
                ->get(),
        );

        self::assertSame($originalId, (int) $reloaded->getKey());
        self::assertSame($originalHash, (string) $reloaded->getAttribute('token_hash'));
        self::assertSame($originalToken, (string) $reloaded->getAttribute('delivery_token'));
        self::assertTrue($reloaded->getAttribute('expires_at')->toImmutable() > $originalExpiry);
        self::assertSame(2, (int) $reloaded->getAttribute('row_version'));
        self::assertSame(2, (int) $updatedOperator->getAttribute('row_version'));
        self::assertCount(2, $deliveries);
        self::assertSame(PlatformOperatorInvitationDeliveryStatus::CANCELLED, $deliveries[0]->getAttribute('status'));
        self::assertSame(PlatformOperatorInvitationDeliveryStatus::QUEUED, $deliveries[1]->getAttribute('status'));
        self::assertSame(2, (int) $deliveries[1]->getAttribute('attempt_number'));

        $inspection = $service->inspect($originalToken);
        self::assertSame('pending.operator@example.test', $inspection['email']);

        Queue::assertPushed(DeliverPlatformOperatorInvitation::class, 2);
        Queue::assertPushed(
            DeliverPlatformOperatorInvitation::class,
            static fn (DeliverPlatformOperatorInvitation $job): bool => $job->invitationId === $originalId
                && $job->deliveryId === (int) $deliveries[1]->getKey(),
        );
    }

    public function test_resend_replaces_a_legacy_digest_with_a_new_stable_invitation(): void
    {
        Queue::fake();
        $executionContext = $this->app->make(TenantExecutionContextInterface::class);
        $codec = $this->app->make(PlatformOperatorInvitationTokenCodec::class);
        $service = $this->service($codec);
        $operator = $this->createOperator($executionContext);
        $legacyToken = $codec->issue();

        $legacyInvitation = $executionContext->runAsControlPlane(function () use ($operator, $legacyToken, $codec): PlatformOperatorInvitationModel {
            $invitation = PlatformOperatorInvitationModel::query()->create([
                'public_id' => (string) \Illuminate\Support\Str::uuid(),
                'platform_operator_id' => $operator->getKey(),
                'created_by_operator_id' => null,
                'token_hash' => $codec->legacyDigest($legacyToken),
                'delivery_token' => $legacyToken,
                'status' => PlatformOperatorInvitationStatus::PENDING,
                'expires_at' => now()->addHour(),
                'row_version' => 1,
            ]);
            PlatformOperatorInvitationDeliveryModel::query()->create([
                'invitation_id' => $invitation->getKey(),
                'attempt_number' => 1,
                'status' => PlatformOperatorInvitationDeliveryStatus::SENT,
                'sent_at' => now(),
                'row_version' => 1,
            ]);

            return $invitation;
        });

        $service->resend((int) $operator->getKey(), 1);

        $invitations = $executionContext->runAsControlPlane(
            fn () => PlatformOperatorInvitationModel::query()
                ->where('platform_operator_id', $operator->getKey())
                ->orderBy('id')
                ->get(),
        );
        self::assertCount(2, $invitations);
        $retired = $invitations[0];
        $replacement = $invitations[1];
        $replacementToken = (string) $replacement->getAttribute('delivery_token');

        self::assertSame($legacyInvitation->getKey(), $retired->getKey());
        self::assertSame(PlatformOperatorInvitationStatus::REVOKED, $retired->getAttribute('status'));
        self::assertStringContainsString('replaced', strtolower((string) $retired->getAttribute('revocation_reason')));
        self::assertSame(PlatformOperatorInvitationStatus::PENDING, $replacement->getAttribute('status'));
        self::assertNotSame($legacyToken, $replacementToken);
        self::assertTrue($codec->matchesCurrentDigest(
            $replacementToken,
            (string) $replacement->getAttribute('token_hash'),
        ));

        try {
            $service->inspect($legacyToken);
            self::fail('The retired legacy invitation should not remain usable.');
        } catch (ValidationException $exception) {
            self::assertSame(
                ['This invitation was replaced. Use the most recent invitation email.'],
                $exception->errors()['token'],
            );
        }

        self::assertSame('pending.operator@example.test', $service->inspect($replacementToken)['email']);
        Queue::assertPushed(DeliverPlatformOperatorInvitation::class, 1);
        Queue::assertPushed(
            DeliverPlatformOperatorInvitation::class,
            static fn (DeliverPlatformOperatorInvitation $job): bool => $job->invitationId === (int) $replacement->getKey(),
        );
    }

    private function service(?PlatformOperatorInvitationTokenCodec $codec = null): PlatformOperatorInvitationService
    {
        $credentials = $this->createMock(PlatformOperatorCredentialProvisionerInterface::class);
        $credentials->method('passwordRequirements')->willReturn([
            'minimum_length' => 12,
            'mixed_case' => true,
            'numbers' => true,
            'symbols' => true,
        ]);
        $currentUser = $this->createMock(CurrentUserContextAccessorInterface::class);
        $currentUser->method('currentUserId')->willReturn(null);

        return new PlatformOperatorInvitationService(
            new PlatformOperatorInvitationModel,
            new PlatformOperatorInvitationDeliveryModel,
            new PlatformOperatorModel,
            $credentials,
            $codec ?? $this->app->make(PlatformOperatorInvitationTokenCodec::class),
            $this->app->make(ClockInterface::class),
            $currentUser,
            $this->app->make(TenantExecutionContextInterface::class),
            $this->app->make(DatabaseManager::class),
            $this->createMock(AuditRecorderInterface::class),
        );
    }

    private function createOperator(TenantExecutionContextInterface $executionContext): PlatformOperatorModel
    {
        return $executionContext->runAsControlPlane(
            fn (): PlatformOperatorModel => PlatformOperatorModel::query()->create([
                'row_version' => 1,
                'first_name' => 'Pending',
                'last_name' => 'Operator',
                'email' => 'pending.operator@example.test',
                'status' => PlatformOperatorStatus::INVITED,
                'invited_at' => now(),
            ]),
        );
    }
}
