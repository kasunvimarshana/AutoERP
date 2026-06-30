<?php

declare(strict_types=1);

namespace Tests\Feature\User;

use Tests\TestCase;

final class PlatformOperatorInvitationValidationTest extends TestCase
{
    public function test_inspection_rejects_a_length_correct_token_with_an_invalid_alphabet(): void
    {
        $this->postJson('/api/v1/platform/operator-invitations/inspect', [
            'token' => str_repeat('A', 71).'!',
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['token']);
    }

    public function test_acceptance_rejects_a_malformed_token_before_credentials_are_provisioned(): void
    {
        $this->postJson('/api/v1/platform/operator-invitations/accept', [
            'token' => str_repeat('A', 71).'.',
            'password' => 'Valid-Platform-Password-2026!',
            'password_confirmation' => 'Valid-Platform-Password-2026!',
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['token']);
    }
}
