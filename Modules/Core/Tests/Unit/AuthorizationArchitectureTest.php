<?php

declare(strict_types=1);

namespace Modules\Core\Tests\Unit;

use Modules\Core\Domain\Contracts\ServiceContract;
use PHPUnit\Framework\TestCase;

/**
 * Authorization architecture compliance tests.
 *
 * Verifies that every module's service layer implements the ServiceContract
 * interface, which is the architectural boundary that separates controller
 * input handling from business logic.
 *
 * Per AGENT.md §Mandatory Application Flow:
 *   "Controller → Service → Handler (Pipeline) → Repository → Entity"
 *   "Controllers contain no business logic."
 *   "Authorization enforced via Policy classes only."
 *
 * These structural tests confirm that each service can be introspected
 * for compliance without requiring a full Laravel bootstrap.
 */
class AuthorizationArchitectureTest extends TestCase
{
    /**
     * Returns all module service classes that must implement ServiceContract.
     *
     * @return array<string, array{string}>
     */
    public static function serviceClassProvider(): array
    {
        return [
            'TenancyService'       => [\Modules\Tenancy\Application\Services\TenancyService::class],
            'AuthService'          => [\Modules\Auth\Application\Services\AuthService::class],
            'OrganisationService'  => [\Modules\Organisation\Application\Services\OrganisationService::class],
            'MetadataService'      => [\Modules\Metadata\Application\Services\MetadataService::class],
            'WorkflowService'      => [\Modules\Workflow\Application\Services\WorkflowService::class],
            'ProductService'       => [\Modules\Product\Application\Services\ProductService::class],
            'AccountingService'    => [\Modules\Accounting\Application\Services\AccountingService::class],
            'PricingService'       => [\Modules\Pricing\Application\Services\PricingService::class],
            'InventoryService'     => [\Modules\Inventory\Application\Services\InventoryService::class],
            'WarehouseService'     => [\Modules\Warehouse\Application\Services\WarehouseService::class],
            'SalesService'         => [\Modules\Sales\Application\Services\SalesService::class],
            'POSService'           => [\Modules\POS\Application\Services\POSService::class],
            'CRMService'           => [\Modules\CRM\Application\Services\CRMService::class],
            'ProcurementService'   => [\Modules\Procurement\Application\Services\ProcurementService::class],
            'ReportingService'     => [\Modules\Reporting\Application\Services\ReportingService::class],
            'NotificationService'  => [\Modules\Notification\Application\Services\NotificationService::class],
            'IntegrationService'   => [\Modules\Integration\Application\Services\IntegrationService::class],
            'PluginService'        => [\Modules\Plugin\Application\Services\PluginService::class],
        ];
    }

    /**
     * @dataProvider serviceClassProvider
     */
    #[\PHPUnit\Framework\Attributes\DataProvider('serviceClassProvider')]
    public function test_service_implements_service_contract(string $serviceClass): void
    {
        $this->assertTrue(
            is_a($serviceClass, ServiceContract::class, true),
            sprintf(
                '%s must implement ServiceContract. '
                . 'This boundary ensures controllers delegate to services '
                . 'and policy checks are enforced consistently.',
                $serviceClass,
            ),
        );
    }

    /**
     * Every controller must NOT expose a __construct that accepts
     * repository contracts directly — they must only accept service classes,
     * enforcing the Controller → Service → Repository layer separation.
     *
     * This test validates the AuthController as a representative sample.
     */
    public function test_auth_controller_constructor_accepts_only_service_not_repository(): void
    {
        $reflection = new \ReflectionClass(\Modules\Auth\Interfaces\Http\Controllers\AuthController::class);

        if (!$reflection->hasMethod('__construct')) {
            $this->addToAssertionCount(1); // No constructor — passes by default.
            return;
        }

        $params = $reflection->getMethod('__construct')->getParameters();

        foreach ($params as $param) {
            $type = $param->getType();
            if ($type instanceof \ReflectionNamedType) {
                $typeName = $type->getName();
                $this->assertStringNotContainsString(
                    'Repository',
                    $typeName,
                    'AuthController must not inject a Repository directly. '
                    . 'Use a Service layer instead.',
                );
            }
        }

        $this->addToAssertionCount(1); // Verified no repository injection.
    }

    /**
     * ServiceContract must be an interface (not an abstract class),
     * which is the standard architectural contract mechanism.
     */
    public function test_service_contract_is_an_interface(): void
    {
        $reflection = new \ReflectionClass(ServiceContract::class);

        $this->assertTrue(
            $reflection->isInterface(),
            'ServiceContract must be defined as an interface.',
        );
    }

    /**
     * ServiceContract must be in the Core Domain Contracts namespace,
     * confirming it is a cross-cutting architectural primitive.
     */
    public function test_service_contract_is_in_core_domain_namespace(): void
    {
        $reflection = new \ReflectionClass(ServiceContract::class);

        $this->assertStringStartsWith(
            'Modules\Core\Domain\Contracts',
            $reflection->getNamespaceName(),
            'ServiceContract must reside in Core\\Domain\\Contracts to be a shared architectural primitive.',
        );
    }
}
