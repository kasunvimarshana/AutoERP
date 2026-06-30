<?php

declare(strict_types=1);

namespace Tests\Feature\User;

use PHPUnit\Framework\TestCase;

final class PlatformOperatorInvitationResendContractTest extends TestCase
{
    private string $root;

    protected function setUp(): void
    {
        parent::setUp();
        $this->root = dirname(__DIR__, 3);
    }

    public function test_resend_reuses_a_live_invitation_token_and_creates_a_new_delivery_attempt(): void
    {
        $source = $this->source(
            'app/Modules/User/Services/Platform/Invitations/PlatformOperatorInvitationService.php',
        );

        $resend = $this->method($source, 'resend', 'revoke');
        $redelivery = $this->method($source, 'queueRedelivery', 'retireUnavailableInvitation');

        self::assertStringContainsString('$this->latestPendingInvitation($operatorId)', $resend);
        self::assertStringContainsString('$this->canRedeliver($invitation)', $resend);
        self::assertStringContainsString('$this->queueRedelivery($invitation)', $resend);
        self::assertStringContainsString('$this->issueForOperator($operator)', $resend);
        self::assertStringContainsString('$this->createDelivery($invitation, $nextAttempt)', $redelivery);
        self::assertStringContainsString("'expires_at' => \$this->invitationExpiry()", $redelivery);
        self::assertStringNotContainsString("'token_hash' =>", $redelivery);
        self::assertStringNotContainsString("'delivery_token' =>", $redelivery);
    }

    public function test_delivery_jobs_are_bound_to_one_exact_attempt(): void
    {
        $job = $this->source('app/Modules/User/Jobs/DeliverPlatformOperatorInvitation.php');
        $delivery = $this->source(
            'app/Modules/User/Services/Platform/Invitations/PlatformOperatorInvitationDeliveryService.php',
        );

        self::assertStringContainsString('public readonly int $deliveryId', $job);
        self::assertStringContainsString('return (string) $this->deliveryId;', $job);
        self::assertStringContainsString('$delivery->deliver($this->deliveryId);', $job);
        self::assertStringContainsString('->releaseAfter(self::OVERLAP_RELEASE_SECONDS)', $job);
        self::assertStringNotContainsString('->dontRelease()', $job);

        self::assertStringContainsString('public function deliver(int $deliveryId): void', $delivery);
        self::assertStringContainsString('private function claim(int $deliveryId): ?array', $delivery);
        self::assertStringContainsString('->whereKey($deliveryId)', $delivery);
        self::assertStringNotContainsString("->latest('attempt_number')", $delivery);
        self::assertStringNotContainsString("'attempt_number' => (int) \$delivery->getAttribute('attempt_number') + 1", $delivery);
    }

    public function test_known_stale_invitation_states_return_actionable_messages(): void
    {
        $source = $this->source(
            'app/Modules/User/Services/Platform/Invitations/PlatformOperatorInvitationService.php',
        );

        self::assertStringContainsString('This invitation has already been used. Return to sign in.', $source);
        self::assertStringContainsString('This invitation was replaced. Use the most recent invitation email.', $source);
        self::assertStringContainsString('This invitation was revoked. Ask a platform manager to send a new one.', $source);
        self::assertStringContainsString('This invitation has expired. Ask a platform manager to send a new one.', $source);
    }

    private function source(string $relativePath): string
    {
        $path = $this->root.'/'.$relativePath;
        self::assertFileExists($path);
        $source = file_get_contents($path);
        self::assertIsString($source);

        return $source;
    }

    private function method(string $source, string $method, string $nextMethod): string
    {
        $start = strpos($source, "public function {$method}(");
        if ($start === false) {
            $start = strpos($source, "private function {$method}(");
        }
        self::assertNotFalse($start, "Method {$method} was not found.");

        $end = strpos($source, "public function {$nextMethod}(", $start + 1);
        if ($end === false) {
            $end = strpos($source, "private function {$nextMethod}(", $start + 1);
        }
        self::assertNotFalse($end, "Method {$nextMethod} was not found.");

        return substr($source, $start, $end - $start);
    }
}
