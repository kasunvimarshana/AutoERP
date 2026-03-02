<?php

declare(strict_types=1);

namespace Modules\Auth\Tests\Unit;

use Modules\Auth\Domain\ValueObjects\Password;
use PHPUnit\Framework\TestCase;

/**
 * Tests for the Password value object.
 */
class PasswordTest extends TestCase
{
    // ── Construction from plain text ──────────────────────────────────────

    public function test_from_plain_text_produces_non_empty_hash(): void
    {
        $password = Password::fromPlainText('SecurePass1');

        $this->assertNotEmpty($password->getHashedValue());
    }

    public function test_from_plain_text_produces_bcrypt_hash(): void
    {
        $password = Password::fromPlainText('SecurePass1');

        $this->assertStringStartsWith('$2y$', $password->getHashedValue());
    }

    public function test_password_shorter_than_8_chars_throws_exception(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        Password::fromPlainText('short');
    }

    public function test_password_exactly_8_chars_is_valid(): void
    {
        $password = Password::fromPlainText('exactly8');

        $this->assertNotEmpty($password->getHashedValue());
    }

    // ── Construction from hash ────────────────────────────────────────────

    public function test_from_hash_stores_provided_hash(): void
    {
        $hash     = '$2y$12$somefakehashvaluexyzabc';
        $password = Password::fromHash($hash);

        $this->assertSame($hash, $password->getHashedValue());
    }

    // ── Verification ─────────────────────────────────────────────────────

    public function test_verify_returns_true_for_correct_plain_text(): void
    {
        $plain    = 'MySecret99';
        $password = Password::fromPlainText($plain);

        $this->assertTrue($password->verify($plain));
    }

    public function test_verify_returns_false_for_wrong_plain_text(): void
    {
        $password = Password::fromPlainText('CorrectPass1');

        $this->assertFalse($password->verify('WrongPass1'));
    }

    public function test_two_hashes_of_same_plain_text_are_different(): void
    {
        // bcrypt uses a random salt each time
        $a = Password::fromPlainText('SamePassword1');
        $b = Password::fromPlainText('SamePassword1');

        $this->assertNotSame($a->getHashedValue(), $b->getHashedValue());
    }
}
