<?php

declare(strict_types=1);

namespace Tests\Unit\Architecture;

use PHPUnit\Framework\TestCase;
use ReflectionMethod;

/**
 * Architecture guardrails: every Purchase line-list repository method that
 * enumerates child lines MUST require an explicit tenantId as its first
 * parameter. This prevents cross-tenant data leaks caused by passing only
 * a parent entity ID, which is only unique within a tenant, not globally.
 */
class PurchaseLineRepositoryGuardrailsTest extends TestCase
{
    // -----------------------------------------------------------------------
    // Interfaces
    // -----------------------------------------------------------------------

    private const INTERFACES = [
        \Modules\Purchase\Domain\RepositoryInterfaces\PurchaseOrderLineRepositoryInterface::class   => 'findByPurchaseOrderId',
        \Modules\Purchase\Domain\RepositoryInterfaces\PurchaseInvoiceLineRepositoryInterface::class => 'findByInvoiceId',
        \Modules\Purchase\Domain\RepositoryInterfaces\GrnLineRepositoryInterface::class             => 'findByGrnHeaderId',
        \Modules\Purchase\Domain\RepositoryInterfaces\PurchaseReturnLineRepositoryInterface::class  => 'findByPurchaseReturnId',
    ];

    // -----------------------------------------------------------------------
    // Implementations
    // -----------------------------------------------------------------------

    private const IMPLEMENTATIONS = [
        \Modules\Purchase\Infrastructure\Persistence\Eloquent\Repositories\EloquentPurchaseOrderLineRepository::class   => 'findByPurchaseOrderId',
        \Modules\Purchase\Infrastructure\Persistence\Eloquent\Repositories\EloquentPurchaseInvoiceLineRepository::class => 'findByInvoiceId',
        \Modules\Purchase\Infrastructure\Persistence\Eloquent\Repositories\EloquentGrnLineRepository::class             => 'findByGrnHeaderId',
        \Modules\Purchase\Infrastructure\Persistence\Eloquent\Repositories\EloquentPurchaseReturnLineRepository::class  => 'findByPurchaseReturnId',
    ];

    // -----------------------------------------------------------------------
    // Test: interfaces declare tenantId as the first parameter
    // -----------------------------------------------------------------------

    /**
     * @dataProvider lineListInterfaceProvider
     */
    public function test_purchase_line_list_interface_methods_require_tenant_id_first(
        string $interface,
        string $method
    ): void {
        $this->assertTrue(
            interface_exists($interface),
            "Interface {$interface} must exist."
        );

        $rm = new ReflectionMethod($interface, $method);
        $params = $rm->getParameters();

        $this->assertGreaterThanOrEqual(
            2,
            count($params),
            "{$interface}::{$method} must have at least two parameters (tenantId, parentId)."
        );

        $this->assertSame(
            'tenantId',
            $params[0]->getName(),
            "{$interface}::{$method} first parameter must be named 'tenantId' to enforce tenant isolation."
        );

        $type = $params[0]->getType();
        $typeName = $type instanceof \ReflectionNamedType ? $type->getName() : null;

        $this->assertSame(
            'int',
            $typeName,
            "{$interface}::{$method} 'tenantId' parameter must be typed as int."
        );
    }

    public static function lineListInterfaceProvider(): array
    {
        $cases = [];
        foreach (self::INTERFACES as $interface => $method) {
            $shortName = class_basename($interface);
            $cases["{$shortName}::{$method}"] = [$interface, $method];
        }
        return $cases;
    }

    // -----------------------------------------------------------------------
    // Test: Eloquent implementations honour the tenantId parameter
    // -----------------------------------------------------------------------

    /**
     * @dataProvider lineListImplementationProvider
     */
    public function test_purchase_line_list_implementation_methods_require_tenant_id_first(
        string $class,
        string $method
    ): void {
        $this->assertTrue(
            class_exists($class),
            "Implementation class {$class} must exist."
        );

        $rm = new ReflectionMethod($class, $method);
        $params = $rm->getParameters();

        $this->assertGreaterThanOrEqual(
            2,
            count($params),
            "{$class}::{$method} must have at least two parameters (tenantId, parentId)."
        );

        $this->assertSame(
            'tenantId',
            $params[0]->getName(),
            "{$class}::{$method} first parameter must be named 'tenantId' to enforce tenant isolation."
        );

        $type = $params[0]->getType();
        $typeName = $type instanceof \ReflectionNamedType ? $type->getName() : null;

        $this->assertSame(
            'int',
            $typeName,
            "{$class}::{$method} 'tenantId' parameter must be typed as int."
        );
    }

    public static function lineListImplementationProvider(): array
    {
        $cases = [];
        foreach (self::IMPLEMENTATIONS as $class => $method) {
            $shortName = class_basename($class);
            $cases["{$shortName}::{$method}"] = [$class, $method];
        }
        return $cases;
    }

    // -----------------------------------------------------------------------
    // Test: implementations query the tenant_id column
    // -----------------------------------------------------------------------

    /**
     * The Eloquent repository bodies must contain `tenant_id` in their
     * line-list query to prevent full-table cross-tenant leaks.
     */
    public function test_purchase_line_list_implementations_filter_by_tenant_id_in_query(): void
    {
        $implDir = dirname(__DIR__, 3)
            . '/app/Modules/Purchase/Infrastructure/Persistence/Eloquent/Repositories/';

        $fileMethodMap = [
            'EloquentPurchaseOrderLineRepository.php'   => 'findByPurchaseOrderId',
            'EloquentPurchaseInvoiceLineRepository.php'  => 'findByInvoiceId',
            'EloquentGrnLineRepository.php'              => 'findByGrnHeaderId',
            'EloquentPurchaseReturnLineRepository.php'   => 'findByPurchaseReturnId',
        ];

        foreach ($fileMethodMap as $file => $method) {
            $path = $implDir . $file;
            $this->assertFileExists($path, "Repository file {$file} must exist.");

            $source = file_get_contents($path);

            // Extract the method body by finding its opening brace and
            // scanning for the matching close brace.
            $pattern = '/public function ' . preg_quote($method, '/') . '\b[^{]*\{/';
            $this->assertMatchesRegularExpression(
                $pattern,
                $source,
                "{$file} must contain method {$method}."
            );

            // Locate start of method
            preg_match($pattern, $source, $matches, PREG_OFFSET_CAPTURE);
            $start  = $matches[0][1] + strlen($matches[0][0]);
            $depth  = 1;
            $pos    = $start;
            $len    = strlen($source);

            while ($pos < $len && $depth > 0) {
                if ($source[$pos] === '{') {
                    $depth++;
                } elseif ($source[$pos] === '}') {
                    $depth--;
                }
                $pos++;
            }

            $body = substr($source, $start, $pos - $start - 1);

            $this->assertStringContainsString(
                'tenant_id',
                $body,
                "{$file}::{$method} body must reference 'tenant_id' to enforce tenant isolation."
            );
        }
    }
}
