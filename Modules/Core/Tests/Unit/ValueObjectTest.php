<?php

declare(strict_types=1);

namespace Modules\Core\Tests\Unit;

use InvalidArgumentException;
use Modules\Core\Domain\ValueObjects\ValueObject;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for the ValueObject base class.
 *
 * Tests structural equality, type-safety, and the ensure() guard.
 * A concrete anonymous subclass is used to exercise the abstract methods.
 */
class ValueObjectTest extends TestCase
{
    // -------------------------------------------------------------------------
    // Factory helper
    // -------------------------------------------------------------------------

    private function makeValueObject(array $data): ValueObject
    {
        return new class($data) extends ValueObject {
            public function __construct(private array $fields) {}

            public function toArray(): array
            {
                return $this->fields;
            }
        };
    }

    // -------------------------------------------------------------------------
    // equals — structural equality by value
    // -------------------------------------------------------------------------

    public function test_equals_returns_true_for_same_values(): void
    {
        $a = $this->makeValueObject(['currency' => 'USD', 'amount' => '100.0000']);
        $b = $this->makeValueObject(['currency' => 'USD', 'amount' => '100.0000']);

        $this->assertTrue($a->equals($b));
    }

    public function test_equals_returns_false_for_different_values(): void
    {
        $a = $this->makeValueObject(['currency' => 'USD', 'amount' => '100.0000']);
        $b = $this->makeValueObject(['currency' => 'USD', 'amount' => '200.0000']);

        $this->assertFalse($a->equals($b));
    }

    public function test_equals_returns_false_for_different_classes(): void
    {
        $a = $this->makeValueObject(['amount' => '100.0000']);

        // A second anonymous class with the same data — different class identity.
        $b = new class(['amount' => '100.0000']) extends ValueObject {
            public function __construct(private array $fields) {}

            public function toArray(): array
            {
                return $this->fields;
            }
        };

        $this->assertFalse($a->equals($b));
    }

    public function test_equals_is_reflexive(): void
    {
        $a = $this->makeValueObject(['code' => 'EUR']);

        $this->assertTrue($a->equals($a));
    }

    public function test_equals_uses_key_order(): void
    {
        // toArray() results are compared with ===; key order matters.
        $a = $this->makeValueObject(['a' => '1', 'b' => '2']);
        $b = $this->makeValueObject(['b' => '2', 'a' => '1']);

        // Different key order produces a different array, so they are not equal.
        $this->assertFalse($a->equals($b));
    }

    // -------------------------------------------------------------------------
    // toArray — returns the declared fields
    // -------------------------------------------------------------------------

    public function test_to_array_returns_correct_data(): void
    {
        $data = ['tenant_id' => 7, 'slug' => 'acme'];
        $vo   = $this->makeValueObject($data);

        $this->assertSame($data, $vo->toArray());
    }

    // -------------------------------------------------------------------------
    // ensure — guard helper
    // -------------------------------------------------------------------------

    public function test_ensure_does_not_throw_when_condition_is_true(): void
    {
        $vo = new class extends ValueObject {
            public function toArray(): array { return []; }

            public function callEnsure(bool $condition, string $message): void
            {
                $this->ensure($condition, $message);
            }
        };

        // Should not throw
        $vo->callEnsure(true, 'Should not throw');
        $this->assertTrue(true);
    }

    public function test_ensure_throws_when_condition_is_false(): void
    {
        $vo = new class extends ValueObject {
            public function toArray(): array { return []; }

            public function callEnsure(bool $condition, string $message): void
            {
                $this->ensure($condition, $message);
            }
        };

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Value must be positive');

        $vo->callEnsure(false, 'Value must be positive');
    }
}
