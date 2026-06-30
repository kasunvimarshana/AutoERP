<?php

declare(strict_types=1);

namespace Tests\Feature\Auth;

use Tests\TestCase;

final class InitialAdministratorInvitationValidationTest extends TestCase
{
    public function test_inspection_rejects_a_length_correct_token_with_an_invalid_alphabet(): void
    {
        $this->postJson('/api/v1/auth/initial-administrator/inspect', [
            'token' => str_repeat('a', 63).'!',
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['token']);
    }

    public function test_acceptance_rejects_a_query_style_or_uppercase_token_before_business_logic_runs(): void
    {
        $this->postJson('/api/v1/auth/initial-administrator/accept', [
            'token' => str_repeat('A', 64),
            'first_name' => 'Kasun',
            'last_name' => 'Admin',
            'password' => 'StrongPassword!123',
            'password_confirmation' => 'StrongPassword!123',
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['token']);
    }
}
