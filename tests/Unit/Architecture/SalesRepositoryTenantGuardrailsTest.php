<?php

declare(strict_types=1);

namespace Tests\Unit\Architecture;

use PHPUnit\Framework\TestCase;
use ReflectionMethod;
use ReflectionNamedType;

/**
 * Guardrails for Sales aggregate lookup contracts.
 *
 * All Sales aggregate repositories must keep tenant-scoped lookup methods with
 * tenantId as the first, int-typed parameter and query-level tenant_id filters.
 */
class SalesRepositoryTenantGuardrailsTest extends TestCase
{
    /**
     * @return array<string, array{0: class-string, 1: string}>
     */
    public static function lookupInterfaceProvider(): array
    {
        return [
            'SalesOrderRepositoryInterface::findByTenantAndSoNumber' => [
                \Modules\Sales\Domain\RepositoryInterfaces\SalesOrderRepositoryInterface::class,
                'findByTenantAndSoNumber',
            ],
            'SalesInvoiceRepositoryInterface::findByTenantAndInvoiceNumber' => [
                \Modules\Sales\Domain\RepositoryInterfaces\SalesInvoiceRepositoryInterface::class,
                'findByTenantAndInvoiceNumber',
            ],
            'ShipmentRepositoryInterface::findByTenantAndShipmentNumber' => [
                \Modules\Sales\Domain\RepositoryInterfaces\ShipmentRepositoryInterface::class,
                'findByTenantAndShipmentNumber',
            ],
            'SalesReturnRepositoryInterface::findByTenantAndReturnNumber' => [
                \Modules\Sales\Domain\RepositoryInterfaces\SalesReturnRepositoryInterface::class,
                'findByTenantAndReturnNumber',
            ],
        ];
    }

    /**
     * @return array<string, array{0: class-string, 1: string}>
     */
    public static function lookupImplementationProvider(): array
    {
        return [
            'EloquentSalesOrderRepository::findByTenantAndSoNumber' => [
                \Modules\Sales\Infrastructure\Persistence\Eloquent\Repositories\EloquentSalesOrderRepository::class,
                'findByTenantAndSoNumber',
            ],
            'EloquentSalesInvoiceRepository::findByTenantAndInvoiceNumber' => [
                \Modules\Sales\Infrastructure\Persistence\Eloquent\Repositories\EloquentSalesInvoiceRepository::class,
                'findByTenantAndInvoiceNumber',
            ],
            'EloquentShipmentRepository::findByTenantAndShipmentNumber' => [
                \Modules\Sales\Infrastructure\Persistence\Eloquent\Repositories\EloquentShipmentRepository::class,
                'findByTenantAndShipmentNumber',
            ],
            'EloquentSalesReturnRepository::findByTenantAndReturnNumber' => [
                \Modules\Sales\Infrastructure\Persistence\Eloquent\Repositories\EloquentSalesReturnRepository::class,
                'findByTenantAndReturnNumber',
            ],
        ];
    }

    /**
     * @dataProvider lookupInterfaceProvider
     */
    public function test_sales_lookup_interface_methods_require_tenant_id_first(string $interface, string $method): void
    {
        $this->assertTrue(interface_exists($interface), "Interface {$interface} must exist.");

        $rm = new ReflectionMethod($interface, $method);
        $params = $rm->getParameters();

        $this->assertGreaterThanOrEqual(2, count($params), "{$interface}::{$method} must have tenant + business key params.");
        $this->assertSame('tenantId', $params[0]->getName(), "{$interface}::{$method} first parameter must be tenantId.");

        $type = $params[0]->getType();
        $this->assertInstanceOf(ReflectionNamedType::class, $type, "{$interface}::{$method} tenantId must be named type.");
        $this->assertSame('int', $type->getName(), "{$interface}::{$method} tenantId must be int.");
    }

    /**
     * @dataProvider lookupImplementationProvider
     */
    public function test_sales_lookup_implementation_methods_require_tenant_id_first(string $class, string $method): void
    {
        $this->assertTrue(class_exists($class), "Class {$class} must exist.");

        $rm = new ReflectionMethod($class, $method);
        $params = $rm->getParameters();

        $this->assertGreaterThanOrEqual(2, count($params), "{$class}::{$method} must have tenant + business key params.");
        $this->assertSame('tenantId', $params[0]->getName(), "{$class}::{$method} first parameter must be tenantId.");

        $type = $params[0]->getType();
        $this->assertInstanceOf(ReflectionNamedType::class, $type, "{$class}::{$method} tenantId must be named type.");
        $this->assertSame('int', $type->getName(), "{$class}::{$method} tenantId must be int.");
    }

    public function test_sales_lookup_implementations_keep_tenant_id_query_filter(): void
    {
        $repoRoot = dirname(__DIR__, 3);

        $fileMethodMap = [
            'app/Modules/Sales/Infrastructure/Persistence/Eloquent/Repositories/EloquentSalesOrderRepository.php' => 'findByTenantAndSoNumber',
            'app/Modules/Sales/Infrastructure/Persistence/Eloquent/Repositories/EloquentSalesInvoiceRepository.php' => 'findByTenantAndInvoiceNumber',
            'app/Modules/Sales/Infrastructure/Persistence/Eloquent/Repositories/EloquentShipmentRepository.php' => 'findByTenantAndShipmentNumber',
            'app/Modules/Sales/Infrastructure/Persistence/Eloquent/Repositories/EloquentSalesReturnRepository.php' => 'findByTenantAndReturnNumber',
        ];

        foreach ($fileMethodMap as $relativePath => $methodName) {
            $absolutePath = $repoRoot.DIRECTORY_SEPARATOR.$relativePath;
            $this->assertFileExists($absolutePath, "Expected source file not found: {$relativePath}");

            $source = file_get_contents($absolutePath);
            $this->assertNotFalse($source, "Unable to read source file: {$relativePath}");

            $pattern = '/public function '.preg_quote($methodName, '/').'\b[^{]*\{/';
            $this->assertMatchesRegularExpression($pattern, $source, "Method {$methodName} not found in {$relativePath}");

            preg_match($pattern, $source, $matches, PREG_OFFSET_CAPTURE);
            $start = $matches[0][1] + strlen($matches[0][0]);
            $depth = 1;
            $index = $start;
            $sourceLength = strlen($source);

            while ($index < $sourceLength && $depth > 0) {
                if ($source[$index] === '{') {
                    $depth++;
                } elseif ($source[$index] === '}') {
                    $depth--;
                }

                $index++;
            }

            $methodBody = substr($source, $start, $index - $start - 1);

            $this->assertStringContainsString(
                'where(\'tenant_id\', $tenantId)',
                $methodBody,
                "{$relativePath}::{$methodName} must filter by tenant_id."
            );
        }
    }
}
