<?php

declare(strict_types=1);

namespace Tests\Feature\User;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Modules\User\Constants\PlatformOperatorInvitationDeliveryStatus;
use Modules\User\Constants\PlatformOperatorInvitationStatus;
use Modules\User\Constants\PlatformOperatorStatus;
use Modules\User\Notifications\PlatformOperatorInvitationNotification;
use Modules\User\Services\Platform\Invitations\PlatformOperatorInvitationDeliveryService;
use Modules\User\Services\Platform\Invitations\PlatformOperatorInvitationTokenCodec;
use Tests\TestCase;

final class PlatformOperatorInvitationDeliveryRecoveryTest extends TestCase
{
    use RefreshDatabase;

    public function test_unreadable_delivery_token_is_retired_without_mail_or_retry_exception(): void
    {
        Notification::fake();
        $now = now();
        $operatorId = (int) DB::table('platform_operators')->insertGetId([
            'row_version' => 1,
            'first_name' => 'Unreadable',
            'last_name' => 'Invitation',
            'email' => 'unreadable@example.test',
            'status' => PlatformOperatorStatus::INVITED,
            'credentials_ready_at' => null,
            'invited_at' => $now,
            'activated_at' => null,
            'deactivated_at' => null,
            'created_by_operator_id' => null,
            'updated_by_operator_id' => null,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $codec = $this->app->make(PlatformOperatorInvitationTokenCodec::class);
        $token = $codec->issue();
        $invitationId = (int) DB::table('platform_operator_invitations')->insertGetId([
            'public_id' => (string) Str::uuid(),
            'platform_operator_id' => $operatorId,
            'created_by_operator_id' => null,
            'token_hash' => $codec->digest($token),
            'delivery_token' => 'not-a-valid-encrypted-payload',
            'status' => PlatformOperatorInvitationStatus::PENDING,
            'expires_at' => $now->copy()->addHour(),
            'accepted_at' => null,
            'revoked_at' => null,
            'revocation_reason' => null,
            'row_version' => 1,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        $deliveryId = (int) DB::table('platform_operator_invitation_deliveries')->insertGetId([
            'invitation_id' => $invitationId,
            'attempt_number' => 1,
            'status' => PlatformOperatorInvitationDeliveryStatus::QUEUED,
            'claim_token' => null,
            'claimed_at' => null,
            'lease_expires_at' => null,
            'sent_at' => null,
            'failed_at' => null,
            'mail_provider' => null,
            'error_code' => null,
            'error_message' => null,
            'row_version' => 1,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $this->app->make(PlatformOperatorInvitationDeliveryService::class)->deliver($deliveryId);

        $this->assertDatabaseHas('platform_operator_invitations', [
            'id' => $invitationId,
            'status' => PlatformOperatorInvitationStatus::REVOKED,
        ]);
        $this->assertDatabaseHas('platform_operator_invitation_deliveries', [
            'id' => $deliveryId,
            'status' => PlatformOperatorInvitationDeliveryStatus::CANCELLED,
            'error_code' => 'PLATFORM_OPERATOR_INVITATION_TOKEN_REISSUE_REQUIRED',
        ]);
        Notification::assertNothingSent();
        Notification::assertNotSentTo(
            new \Illuminate\Notifications\AnonymousNotifiable,
            PlatformOperatorInvitationNotification::class,
        );
    }
}
