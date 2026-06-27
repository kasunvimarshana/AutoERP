<?php

declare(strict_types=1);

namespace Tests\Feature\Auth;

use PHPUnit\Framework\TestCase;

final class InvitationDeliveryClaimSafetyTest extends TestCase
{
    private string $root;

    protected function setUp(): void
    {
        parent::setUp();
        $this->root = dirname(__DIR__, 3);
    }

    public function test_tenant_invitation_delivery_revalidates_the_exact_live_claim_before_mail_handoff(): void
    {
        $source = $this->source(
            'app/Modules/Auth/Services/Registration/RegistrationInvitationDeliveryService.php',
        );

        $revalidation = $this->position(
            $source,
            'fn (): bool => $this->isClaimSendable($tenantId, $deliveryId, $claim)',
        );
        $mailHandoff = $this->position($source, "Notification::route('mail'");
        $finalization = $this->position($source, '$this->finalizeSent($tenantId, $deliveryId, $claim)');

        self::assertLessThan($mailHandoff, $revalidation);
        self::assertLessThan($finalization, $mailHandoff);
        self::assertStringContainsString("->where('lease_expires_at', '>', \$this->clock->now())", $source);
        self::assertStringContainsString("\$query->whereKey((int) \$claim['invitation_id'])", $source);
        self::assertStringContainsString("->where('row_version', (int) \$claim['invitation_row_version'])", $source);
        self::assertStringContainsString("->where('email', (string) \$claim['email'])", $source);
        self::assertStringContainsString("->where('claim_token', (string) \$claim['claim_token'])", $source);
        self::assertStringContainsString("->where('row_version', (int) \$claim['row_version'])", $source);
    }

    public function test_platform_invitation_delivery_revalidates_recipient_and_claim_before_mail_handoff(): void
    {
        $source = $this->source(
            'app/Modules/User/Services/Platform/Invitations/PlatformOperatorInvitationDeliveryService.php',
        );

        $revalidation = $this->position(
            $source,
            'fn (): bool => $this->isClaimSendable($claim)',
        );
        $mailHandoff = $this->position($source, "Notification::route('mail'");
        $finalization = $this->position($source, '$this->finalizeSent($claim)');

        self::assertLessThan($mailHandoff, $revalidation);
        self::assertLessThan($finalization, $mailHandoff);
        self::assertStringContainsString("->where('lease_expires_at', '>', \$this->clock->now())", $source);
        self::assertStringContainsString("->whereHas('invitation'", $source);
        self::assertStringContainsString("->whereHas('operator'", $source);
        self::assertStringContainsString("->where('row_version', (int) \$claim['invitation_row_version'])", $source);
        self::assertStringContainsString("\$operatorQuery->whereKey((int) \$claim['operator_id'])", $source);
        self::assertStringContainsString("->where('row_version', (int) \$claim['operator_row_version'])", $source);
        self::assertStringContainsString("->where('email', (string) \$claim['email'])", $source);
        self::assertStringContainsString('PlatformOperatorInvitationStatus::PENDING', $source);
        self::assertStringContainsString('PlatformOperatorStatus::INVITED', $source);
        self::assertStringContainsString('if ($updated !== 1)', $source);
    }

    public function test_platform_worker_closes_open_attempts_when_the_invitation_is_not_sendable(): void
    {
        $source = $this->source(
            'app/Modules/User/Services/Platform/Invitations/PlatformOperatorInvitationDeliveryService.php',
        );

        self::assertStringContainsString('private function cancelOpenDeliveries(', $source);
        self::assertStringContainsString('self::INVITATION_EXPIRED_CODE', $source);
        self::assertStringContainsString('self::INVITATION_UNAVAILABLE_CODE', $source);
        self::assertStringContainsString('PlatformOperatorInvitationDeliveryStatus::CANCELLED', $source);
        self::assertStringContainsString("'claim_token' => null", $source);
        self::assertStringContainsString("'lease_expires_at' => null", $source);
    }

    private function source(string $relativePath): string
    {
        $path = $this->root.'/'.$relativePath;
        self::assertFileExists($path);

        return (string) file_get_contents($path);
    }

    private function position(string $source, string $needle): int
    {
        $position = strpos($source, $needle);
        self::assertNotFalse($position, sprintf('Missing expected source fragment: %s', $needle));

        return $position;
    }
}
