<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\PresenceVerifierInterface;
use Modules\Auth\Application\Contracts\AuthorizationServiceInterface;
use Modules\Tax\Application\Contracts\CreateTaxGroupServiceInterface;
use Modules\Tax\Application\Contracts\CreateTaxRateServiceInterface;
use Modules\Tax\Application\Contracts\CreateTaxRuleServiceInterface;
use Modules\Tax\Application\Contracts\DeleteTaxGroupServiceInterface;
use Modules\Tax\Application\Contracts\DeleteTaxRateServiceInterface;
use Modules\Tax\Application\Contracts\DeleteTaxRuleServiceInterface;
use Modules\Tax\Application\Contracts\FindTaxGroupServiceInterface;
use Modules\Tax\Application\Contracts\FindTaxRateServiceInterface;
use Modules\Tax\Application\Contracts\FindTaxRuleServiceInterface;
use Modules\Tax\Application\Contracts\FindTransactionTaxServiceInterface;
use Modules\Tax\Application\Contracts\RecordTransactionTaxesServiceInterface;
use Modules\Tax\Application\Contracts\ResolveTaxServiceInterface;
use Modules\Tax\Application\Contracts\UpdateTaxGroupServiceInterface;
use Modules\Tax\Application\Contracts\UpdateTaxRateServiceInterface;
use Modules\Tax\Application\Contracts\UpdateTaxRuleServiceInterface;
use Modules\Tax\Domain\Entities\TaxGroup;
use Modules\Tax\Domain\Entities\TaxRate;
use Modules\Tax\Domain\Entities\TaxRule;
use Modules\Tax\Domain\Entities\TransactionTax;
use Modules\User\Infrastructure\Persistence\Eloquent\Models\UserModel;
use Tests\TestCase;

class TaxEndpointsAuthenticatedTest extends TestCase
{
    use RefreshDatabase;

    private UserModel $authUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->authUser = new UserModel([
            'name' => 'Tax Admin',
            'email' => 'tax-admin@example.com',
            'password' => 'hashed',
        ]);
        $this->authUser->setAttribute('id', 99);
        $this->authUser->setAttribute('tenant_id', 1);

        $authorizationService = $this->createMock(AuthorizationServiceInterface::class);
        $authorizationService->method('can')->willReturn(true);
        $this->app->instance(AuthorizationServiceInterface::class, $authorizationService);

        $presenceVerifier = $this->createMock(PresenceVerifierInterface::class);
        $presenceVerifier->method('getCount')->willReturnCallback(
            static function (string $collection, string $column): int {
                if (
                    ($collection === 'tax_groups' && $column === 'name')
                    || ($collection === 'tax_rates' && $column === 'name')
                ) {
                    return 0;
                }

                return 1;
            }
        );
        $presenceVerifier->method('getMultiCount')->willReturn(1);
        $this->app['validator']->setPresenceVerifier($presenceVerifier);

        // Setup default mocks for all services
        $createTaxGroupService = $this->createMock(CreateTaxGroupServiceInterface::class);
        $createTaxGroupService->method('execute')->willReturn(new TaxGroup(tenantId: 1, name: 'Standard Tax', id: 1));
        $this->app->instance(CreateTaxGroupServiceInterface::class, $createTaxGroupService);

        $findTaxGroupService = $this->createMock(FindTaxGroupServiceInterface::class);
        $findTaxGroupService->method('list')->willReturn(collect([new TaxGroup(tenantId: 1, name: 'Standard Tax', id: 1)]));
        $findTaxGroupService->method('find')->willReturn(new TaxGroup(tenantId: 1, name: 'Standard Tax', id: 1));
        $this->app->instance(FindTaxGroupServiceInterface::class, $findTaxGroupService);

        $updateTaxGroupService = $this->createMock(UpdateTaxGroupServiceInterface::class);
        $updateTaxGroupService->method('execute')->willReturn(new TaxGroup(tenantId: 1, name: 'Updated Tax', rowVersion: 1, id: 1));
        $this->app->instance(UpdateTaxGroupServiceInterface::class, $updateTaxGroupService);

        $deleteTaxGroupService = $this->createMock(DeleteTaxGroupServiceInterface::class);
        $this->app->instance(DeleteTaxGroupServiceInterface::class, $deleteTaxGroupService);

        $createTaxRateService = $this->createMock(CreateTaxRateServiceInterface::class);
        $createTaxRateService->method('execute')->willReturn(new TaxRate(tenantId: 1, taxGroupId: 1, name: '10% Tax', rate: '10.00', id: 1));
        $this->app->instance(CreateTaxRateServiceInterface::class, $createTaxRateService);

        $findTaxRateService = $this->createMock(FindTaxRateServiceInterface::class);
        $findTaxRateService->method('list')->willReturn(collect([new TaxRate(tenantId: 1, taxGroupId: 1, name: '10% Tax', rate: '10.00', id: 1)]));
        $findTaxRateService->method('find')->willReturn(new TaxRate(tenantId: 1, taxGroupId: 1, name: '10% Tax', rate: '10.00', id: 1));
        $this->app->instance(FindTaxRateServiceInterface::class, $findTaxRateService);

        $updateTaxRateService = $this->createMock(UpdateTaxRateServiceInterface::class);
        $updateTaxRateService->method('execute')->willReturn(new TaxRate(tenantId: 1, taxGroupId: 1, name: '15% Tax', rate: '15.00', rowVersion: 1, id: 1));
        $this->app->instance(UpdateTaxRateServiceInterface::class, $updateTaxRateService);

        $deleteTaxRateService = $this->createMock(DeleteTaxRateServiceInterface::class);
        $this->app->instance(DeleteTaxRateServiceInterface::class, $deleteTaxRateService);

        $createTaxRuleService = $this->createMock(CreateTaxRuleServiceInterface::class);
        $createTaxRuleService->method('execute')->willReturn(new TaxRule(tenantId: 1, taxGroupId: 1, partyType: 'customer', id: 1));
        $this->app->instance(CreateTaxRuleServiceInterface::class, $createTaxRuleService);

        $findTaxRuleService = $this->createMock(FindTaxRuleServiceInterface::class);
        $findTaxRuleService->method('list')->willReturn(collect([new TaxRule(tenantId: 1, taxGroupId: 1, id: 1)]));
        $findTaxRuleService->method('find')->willReturn(new TaxRule(tenantId: 1, taxGroupId: 1, id: 1));
        $this->app->instance(FindTaxRuleServiceInterface::class, $findTaxRuleService);

        $updateTaxRuleService = $this->createMock(UpdateTaxRuleServiceInterface::class);
        $updateTaxRuleService->method('execute')->willReturn(new TaxRule(tenantId: 1, taxGroupId: 1, partyType: 'supplier', rowVersion: 1, id: 1));
        $this->app->instance(UpdateTaxRuleServiceInterface::class, $updateTaxRuleService);

        $deleteTaxRuleService = $this->createMock(DeleteTaxRuleServiceInterface::class);
        $this->app->instance(DeleteTaxRuleServiceInterface::class, $deleteTaxRuleService);

        $resolveTaxService = $this->createMock(ResolveTaxServiceInterface::class);
        $resolveTaxService->method('execute')->willReturn(['tax_amount' => '10.00', 'total' => '110.00']);
        $this->app->instance(ResolveTaxServiceInterface::class, $resolveTaxService);

        $recordTransactionTaxesService = $this->createMock(RecordTransactionTaxesServiceInterface::class);
        $recordTransactionTaxesService->method('execute')->willReturn([
            new TransactionTax(tenantId: 1, referenceType: 'sales_invoice', referenceId: 1, taxRateId: 1, taxableAmount: '100.00', taxAmount: '10.00', taxAccountId: 1, id: 1),
        ]);
        $this->app->instance(RecordTransactionTaxesServiceInterface::class, $recordTransactionTaxesService);

        $findTransactionTaxService = $this->createMock(FindTransactionTaxServiceInterface::class);
        $findTransactionTaxService->method('listByReference')->willReturn([
            new TransactionTax(tenantId: 1, referenceType: 'sales_invoice', referenceId: 1, taxRateId: 1, taxableAmount: '100.00', taxAmount: '10.00', taxAccountId: 1, id: 1),
        ]);
        $this->app->instance(FindTransactionTaxServiceInterface::class, $findTransactionTaxService);
    }

    private function actingAsUser(): static
    {
        return $this->withHeader('X-Tenant-ID', '1')->actingAs(
            $this->authUser,
            (string) config('auth_context.guards.api', config('auth.defaults.guard', 'api'))
        );
    }

    /**
     * TaxGroup Endpoints
     */

    public function test_tax_group_index(): void
    {
        $response = $this->actingAsUser()
            ->getJson('/api/tax/groups');

        $response->assertOk();
    }

    public function test_tax_group_store(): void
    {
        $response = $this->actingAsUser()
            ->postJson('/api/tax/groups', [
                'tenant_id' => 1,
                'name' => 'Standard Tax',
                'description' => 'Standard tax group',
            ]);

        $response->assertCreated();
        $response->assertJsonPath('data.id', 1);
    }

    public function test_tax_group_show(): void
    {
        $response = $this->actingAsUser()
            ->getJson('/api/tax/groups/1');

        $response->assertOk();
        $response->assertJsonPath('data.id', 1);
    }

    public function test_tax_group_update(): void
    {
        $response = $this->actingAsUser()
            ->putJson('/api/tax/groups/1', [
                'tenant_id' => 1,
                'name' => 'Updated Tax',
                'row_version' => 1,
            ]);

        $response->assertOk();
        $response->assertJsonPath('data.id', 1);
    }

    public function test_tax_group_destroy(): void
    {
        $response = $this->actingAsUser()
            ->deleteJson('/api/tax/groups/1');

        $response->assertOk();
        $response->assertJsonPath('message', 'Tax group deleted successfully');
    }

    /**
     * TaxRate Endpoints
     */

    public function test_tax_rate_index(): void
    {
        $response = $this->actingAsUser()
            ->getJson('/api/tax/groups/1/rates');

        $response->assertOk();
    }

    public function test_tax_rate_store(): void
    {
        $response = $this->actingAsUser()
            ->postJson('/api/tax/groups/1/rates', [
                'tenant_id' => 1,
                'name' => '10% Tax',
                'rate' => '10.00',
                'type' => 'percentage',
                'is_active' => true,
            ]);

        $response->assertCreated();
        $response->assertJsonPath('data.id', 1);
    }

    public function test_tax_rate_show(): void
    {
        $response = $this->actingAsUser()
            ->getJson('/api/tax/groups/1/rates/1');

        $response->assertOk();
        $response->assertJsonPath('data.id', 1);
    }

    public function test_tax_rate_update(): void
    {
        $response = $this->actingAsUser()
            ->putJson('/api/tax/groups/1/rates/1', [
                'tenant_id' => 1,
                'name' => '15% Tax',
                'rate' => '15.00',
                'row_version' => 1,
            ]);

        $response->assertOk();
        $response->assertJsonPath('data.id', 1);
    }

    public function test_tax_rate_destroy(): void
    {
        $response = $this->actingAsUser()
            ->deleteJson('/api/tax/groups/1/rates/1');

        $response->assertOk();
        $response->assertJsonPath('message', 'Tax rate deleted successfully');
    }

    /**
     * TaxRule Endpoints
     */

    public function test_tax_rule_index(): void
    {
        $response = $this->actingAsUser()
            ->getJson('/api/tax/groups/1/rules');

        $response->assertOk();
    }

    public function test_tax_rule_store(): void
    {
        $response = $this->actingAsUser()
            ->postJson('/api/tax/groups/1/rules', [
                'tenant_id' => 1,
                'party_type' => 'customer',
                'priority' => 0,
            ]);

        $response->assertCreated();
        $response->assertJsonPath('data.id', 1);
    }

    public function test_tax_rule_show(): void
    {
        $response = $this->actingAsUser()
            ->getJson('/api/tax/groups/1/rules/1');

        $response->assertOk();
        $response->assertJsonPath('data.id', 1);
    }

    public function test_tax_rule_update(): void
    {
        $response = $this->actingAsUser()
            ->putJson('/api/tax/groups/1/rules/1', [
                'tenant_id' => 1,
                'party_type' => 'supplier',
                'row_version' => 1,
            ]);

        $response->assertOk();
        $response->assertJsonPath('data.id', 1);
    }

    public function test_tax_rule_destroy(): void
    {
        $response = $this->actingAsUser()
            ->deleteJson('/api/tax/groups/1/rules/1');

        $response->assertOk();
        $response->assertJsonPath('message', 'Tax rule deleted successfully');
    }

    /**
     * Tax Calculation Endpoints
     */

    public function test_resolve_tax(): void
    {
        $response = $this->actingAsUser()
            ->postJson('/api/tax/resolve', [
                'tenant_id' => 1,
                'taxable_amount' => '100.00',
                'tax_group_id' => 1,
            ]);

        $response->assertOk();
    }

    public function test_record_transaction_taxes(): void
    {
        $response = $this->actingAsUser()
            ->postJson('/api/tax/transactions/sales_invoice/1/lines', [
                'tenant_id' => 1,
                'tax_lines' => [
                    [
                        'tax_rate_id' => 1,
                        'taxable_amount' => '100.00',
                        'tax_amount' => '10.00',
                    ],
                ],
            ]);

        $response->assertOk();
        $response->assertJsonPath('message', 'Transaction taxes recorded successfully');
    }

    public function test_list_transaction_taxes(): void
    {
        $response = $this->actingAsUser()
            ->getJson('/api/tax/transactions/sales_invoice/1/lines?tenant_id=1');

        $response->assertOk();
    }
}
