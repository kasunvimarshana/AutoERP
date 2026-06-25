<?php

declare(strict_types=1);

namespace Tests\Unit\Core;

use InvalidArgumentException;
use LogicException;
use Modules\Core\Support\TenantExecutionContext;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class TenantExecutionContextTest extends TestCase
{
    public function test_tenant_boundary_is_nested_only_for_the_same_tenant_and_is_always_restored(): void
    {
        $context = new TenantExecutionContext();

        self::assertNull($context->tenantId());
        self::assertFalse($context->isActive());

        $result = $context->runForTenant(10, function () use ($context): string {
            self::assertSame(10, $context->tenantId());
            self::assertTrue($context->isActive());

            return $context->runForTenant(10, static fn (): string => 'ok');
        });

        self::assertSame('ok', $result);
        self::assertNull($context->tenantId());
        self::assertFalse($context->isActive());
    }

    public function test_nested_tenant_execution_cannot_switch_tenant(): void
    {
        $context = new TenantExecutionContext();

        $this->expectException(InvalidArgumentException::class);
        $context->runForTenant(10, fn (): mixed => $context->runForTenant(20, static fn (): null => null));
    }

    public function test_context_is_restored_when_tenant_work_throws(): void
    {
        $context = new TenantExecutionContext();

        try {
            $context->runForTenant(10, static function (): never {
                throw new RuntimeException('failure');
            });
            self::fail('Expected tenant work to fail.');
        } catch (RuntimeException) {
            self::assertNull($context->tenantId());
            self::assertFalse($context->isActive());
        }
    }

    public function test_control_plane_is_explicit_and_can_narrow_to_one_tenant(): void
    {
        $context = new TenantExecutionContext();

        $result = $context->runAsControlPlane(function () use ($context): int {
            self::assertTrue($context->isControlPlane());
            self::assertNull($context->tenantId());

            return $context->runForTenant(25, function () use ($context): int {
                self::assertSame(25, $context->tenantId());
                self::assertTrue($context->isControlPlane());

                return 25;
            });
        });

        self::assertSame(25, $result);
        self::assertFalse($context->isControlPlane());
        self::assertNull($context->tenantId());
    }

    public function test_tenant_execution_cannot_broaden_into_control_plane(): void
    {
        $context = new TenantExecutionContext();

        $this->expectException(LogicException::class);
        $context->runForTenant(10, fn (): mixed => $context->runAsControlPlane(static fn (): null => null));
    }
}
