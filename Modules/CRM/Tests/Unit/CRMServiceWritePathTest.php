<?php

declare(strict_types=1);

namespace Modules\CRM\Tests\Unit;

use Modules\CRM\Application\DTOs\CreateLeadDTO;
use Modules\CRM\Application\DTOs\CreateOpportunityDTO;
use Modules\CRM\Application\Services\CRMService;
use Modules\CRM\Domain\Contracts\CRMRepositoryContract;
use Modules\CRM\Domain\Contracts\CrmLeadRepositoryContract;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;

/**
 * Structural compliance tests for CRMService write-path methods.
 *
 * createLead(), convertLeadToOpportunity(), updateOpportunityStage(),
 * closeWon(), and closeLost() call DB::transaction() internally, so
 * functional tests live in feature tests. These pure-PHP tests verify
 * method signatures and DTO field-mapping contracts.
 */
class CRMServiceWritePathTest extends TestCase
{
    // -------------------------------------------------------------------------
    // Method existence — structural compliance
    // -------------------------------------------------------------------------

    public function test_crm_service_has_create_lead_method(): void
    {
        $this->assertTrue(
            method_exists(CRMService::class, 'createLead'),
            'CRMService must expose a public createLead() method.'
        );
    }

    public function test_crm_service_has_convert_lead_to_opportunity_method(): void
    {
        $this->assertTrue(
            method_exists(CRMService::class, 'convertLeadToOpportunity'),
            'CRMService must expose a public convertLeadToOpportunity() method.'
        );
    }

    public function test_crm_service_has_update_opportunity_stage_method(): void
    {
        $this->assertTrue(
            method_exists(CRMService::class, 'updateOpportunityStage'),
            'CRMService must expose a public updateOpportunityStage() method.'
        );
    }

    public function test_crm_service_has_close_won_method(): void
    {
        $this->assertTrue(
            method_exists(CRMService::class, 'closeWon'),
            'CRMService must expose a public closeWon() method.'
        );
    }

    public function test_crm_service_has_close_lost_method(): void
    {
        $this->assertTrue(
            method_exists(CRMService::class, 'closeLost'),
            'CRMService must expose a public closeLost() method.'
        );
    }

    // -------------------------------------------------------------------------
    // Method signatures — parameter inspection via reflection
    // -------------------------------------------------------------------------

    public function test_create_lead_accepts_create_lead_dto(): void
    {
        $reflection = new ReflectionMethod(CRMService::class, 'createLead');
        $params     = $reflection->getParameters();

        $this->assertCount(1, $params);
        $this->assertSame('dto', $params[0]->getName());
        $this->assertSame(CreateLeadDTO::class, (string) $params[0]->getType());
    }

    public function test_convert_lead_accepts_lead_id_and_dto(): void
    {
        $reflection = new ReflectionMethod(CRMService::class, 'convertLeadToOpportunity');
        $params     = $reflection->getParameters();

        $this->assertCount(2, $params);
        $this->assertSame('leadId', $params[0]->getName());
        $this->assertSame('dto', $params[1]->getName());
        $this->assertSame(CreateOpportunityDTO::class, (string) $params[1]->getType());
    }

    public function test_update_opportunity_stage_accepts_opportunity_id_and_stage_id(): void
    {
        $reflection = new ReflectionMethod(CRMService::class, 'updateOpportunityStage');
        $params     = $reflection->getParameters();

        $this->assertCount(2, $params);
        $this->assertSame('opportunityId', $params[0]->getName());
        $this->assertSame('stageId', $params[1]->getName());
    }

    public function test_close_won_accepts_single_opportunity_id(): void
    {
        $reflection = new ReflectionMethod(CRMService::class, 'closeWon');
        $params     = $reflection->getParameters();

        $this->assertCount(1, $params);
        $this->assertSame('opportunityId', $params[0]->getName());
    }

    public function test_close_lost_accepts_single_opportunity_id(): void
    {
        $reflection = new ReflectionMethod(CRMService::class, 'closeLost');
        $params     = $reflection->getParameters();

        $this->assertCount(1, $params);
        $this->assertSame('opportunityId', $params[0]->getName());
    }

    // -------------------------------------------------------------------------
    // CreateLeadDTO — payload mapping contract
    // -------------------------------------------------------------------------

    public function test_create_lead_dto_maps_all_required_fields(): void
    {
        $dto = CreateLeadDTO::fromArray([
            'first_name' => 'Jane',
            'last_name'  => 'Smith',
            'email'      => 'jane@example.com',
            'phone'      => '+1-555-0100',
            'company'    => 'Acme Ltd',
            'source'     => 'web',
            'assigned_to' => 7,
            'campaign_id' => 3,
            'notes'       => 'Interested in tier-2 plan.',
        ]);

        $this->assertSame('Jane', $dto->firstName);
        $this->assertSame('Smith', $dto->lastName);
        $this->assertSame('jane@example.com', $dto->email);
        $this->assertSame('+1-555-0100', $dto->phone);
        $this->assertSame('Acme Ltd', $dto->company);
        $this->assertSame('web', $dto->source);
        $this->assertSame(7, $dto->assignedTo);
        $this->assertSame(3, $dto->campaignId);
        $this->assertSame('Interested in tier-2 plan.', $dto->notes);
    }

    public function test_create_lead_dto_optional_fields_default_to_null(): void
    {
        $dto = CreateLeadDTO::fromArray([
            'first_name' => 'Bob',
            'last_name'  => 'Jones',
        ]);

        $this->assertNull($dto->email);
        $this->assertNull($dto->phone);
        $this->assertNull($dto->company);
        $this->assertNull($dto->source);
        $this->assertNull($dto->assignedTo);
        $this->assertNull($dto->campaignId);
        $this->assertNull($dto->notes);
    }

    // -------------------------------------------------------------------------
    // CreateOpportunityDTO — payload mapping contract
    // -------------------------------------------------------------------------

    public function test_create_opportunity_dto_maps_all_required_fields(): void
    {
        $dto = CreateOpportunityDTO::fromArray([
            'lead_id'           => 10,
            'pipeline_stage_id' => 2,
            'title'             => 'Enterprise Deal',
            'expected_revenue'  => '50000.0000',
            'close_date'        => '2026-06-30',
            'assigned_to'       => 5,
            'probability'       => '75.0000',
            'notes'             => 'High priority.',
        ]);

        $this->assertSame(10, $dto->leadId);
        $this->assertSame(2, $dto->pipelineStageId);
        $this->assertSame('Enterprise Deal', $dto->title);
        $this->assertSame('50000.0000', $dto->expectedRevenue);
        $this->assertSame('2026-06-30', $dto->closeDate);
        $this->assertSame(5, $dto->assignedTo);
        $this->assertSame('75.0000', $dto->probability);
        $this->assertSame('High priority.', $dto->notes);
    }

    public function test_create_opportunity_dto_optional_fields_default_to_null(): void
    {
        $dto = CreateOpportunityDTO::fromArray([
            'pipeline_stage_id' => 1,
            'title'             => 'SMB Deal',
            'expected_revenue'  => '5000.0000',
            'probability'       => '40.0000',
        ]);

        $this->assertNull($dto->leadId);
        $this->assertNull($dto->closeDate);
        $this->assertNull($dto->assignedTo);
        $this->assertNull($dto->notes);
    }

    // -------------------------------------------------------------------------
    // Service instantiation — structural smoke test
    // -------------------------------------------------------------------------

    public function test_crm_service_can_be_instantiated_with_both_repository_contracts(): void
    {
        $service = new CRMService(
            $this->createMock(CRMRepositoryContract::class),
            $this->createMock(CrmLeadRepositoryContract::class),
        );

        $this->assertInstanceOf(CRMService::class, $service);
    }
}
