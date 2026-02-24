<?php

namespace Tests\Unit\Audit;

use Modules\Audit\Domain\Contracts\AuditRepositoryInterface;
use Modules\Audit\Domain\Enums\AuditAction;
use Modules\Audit\Infrastructure\Repositories\AuditRepository;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for the Audit module.
 *
 * AuditService writes directly to the database via the Eloquent model, so its
 * full integration is verified at the feature/integration layer. Here we focus
 * on the pure-domain logic that can be exercised without a database: the
 * AuditAction enum and the data-shape validation helpers.
 */
class AuditServiceTest extends TestCase
{
    // -------------------------------------------------------------------------
    // AuditAction enum
    // -------------------------------------------------------------------------

    public function test_audit_action_enum_has_expected_cases(): void
    {
        $cases = array_map(fn ($c) => $c->value, AuditAction::cases());

        $this->assertContains('created', $cases);
        $this->assertContains('updated', $cases);
        $this->assertContains('deleted', $cases);
        $this->assertContains('viewed', $cases);
        $this->assertContains('exported', $cases);
        $this->assertContains('login', $cases);
        $this->assertContains('logout', $cases);
    }

    public function test_audit_action_from_returns_correct_enum(): void
    {
        $this->assertSame(AuditAction::Created, AuditAction::from('created'));
        $this->assertSame(AuditAction::Updated, AuditAction::from('updated'));
        $this->assertSame(AuditAction::Deleted, AuditAction::from('deleted'));
        $this->assertSame(AuditAction::Login,   AuditAction::from('login'));
        $this->assertSame(AuditAction::Logout,  AuditAction::from('logout'));
    }

    public function test_audit_action_try_from_returns_null_for_unknown_value(): void
    {
        $this->assertNull(AuditAction::tryFrom('unknown_action'));
        $this->assertNull(AuditAction::tryFrom(''));
    }

    public function test_audit_action_values_are_lowercase_strings(): void
    {
        foreach (AuditAction::cases() as $case) {
            $this->assertSame(strtolower($case->value), $case->value,
                "AuditAction::{$case->name} value should be lowercase");
        }
    }

    // -------------------------------------------------------------------------
    // AuditRepositoryInterface — structural integrity
    // -------------------------------------------------------------------------

    public function test_audit_repository_interface_declares_required_methods(): void
    {
        $methods = get_class_methods(AuditRepositoryInterface::class);

        $this->assertContains('paginate', $methods);
        $this->assertContains('findById', $methods);
    }

    public function test_audit_repository_implements_interface(): void
    {
        $this->assertTrue(
            is_a(AuditRepository::class, AuditRepositoryInterface::class, true),
            'AuditRepository must implement AuditRepositoryInterface'
        );
    }
}
