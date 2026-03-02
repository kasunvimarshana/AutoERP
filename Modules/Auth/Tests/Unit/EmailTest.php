<?php

declare(strict_types=1);

namespace Modules\Auth\Tests\Unit;

use Modules\Auth\Domain\ValueObjects\Email;
use PHPUnit\Framework\TestCase;

/**
 * Tests for the Email value object.
 */
class EmailTest extends TestCase
{
    // ── Construction ─────────────────────────────────────────────────────

    public function test_valid_email_is_accepted(): void
    {
        $email = new Email('user@example.com');

        $this->assertSame('user@example.com', $email->getValue());
    }

    public function test_email_is_lowercased(): void
    {
        $email = new Email('User@Example.COM');

        $this->assertSame('user@example.com', $email->getValue());
    }

    public function test_whitespace_is_stripped(): void
    {
        $email = new Email('  admin@test.org  ');

        $this->assertSame('admin@test.org', $email->getValue());
    }

    public function test_email_with_plus_addressing_is_valid(): void
    {
        $email = new Email('user+tag@example.com');

        $this->assertSame('user+tag@example.com', $email->getValue());
    }

    public function test_email_with_subdomain_is_valid(): void
    {
        $email = new Email('user@mail.example.co.uk');

        $this->assertSame('user@mail.example.co.uk', $email->getValue());
    }

    // ── Validation failures ───────────────────────────────────────────────

    public function test_invalid_email_without_at_sign_throws_exception(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        new Email('notanemail');
    }

    public function test_invalid_email_without_domain_throws_exception(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        new Email('user@');
    }

    public function test_empty_string_throws_exception(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        new Email('');
    }

    public function test_whitespace_only_throws_exception(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        new Email('   ');
    }

    // ── Equality ─────────────────────────────────────────────────────────

    public function test_equals_returns_true_for_same_email(): void
    {
        $a = new Email('user@example.com');
        $b = new Email('user@example.com');

        $this->assertTrue($a->equals($b));
    }

    public function test_equals_is_case_insensitive(): void
    {
        $a = new Email('User@Example.com');
        $b = new Email('user@example.com');

        $this->assertTrue($a->equals($b));
    }

    public function test_equals_returns_false_for_different_email(): void
    {
        $a = new Email('alice@example.com');
        $b = new Email('bob@example.com');

        $this->assertFalse($a->equals($b));
    }

    // ── String representation ─────────────────────────────────────────────

    public function test_to_string_returns_normalised_email(): void
    {
        $email = new Email('Admin@EXAMPLE.COM');

        $this->assertSame('admin@example.com', (string) $email);
    }
}
