<?php

declare(strict_types=1);

namespace Modules\CRM\Tests\Unit;

use Modules\CRM\Application\DTOs\CreateLeadDTO;
use Modules\CRM\Application\DTOs\CreateOpportunityDTO;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for CRM DTOs: CreateLeadDTO and CreateOpportunityDTO.
 *
 * Pure PHP — no database or Laravel bootstrap required.
 */
class CRMDTOTest extends TestCase
{
    // -------------------------------------------------------------------------
    // CreateLeadDTO
    // -------------------------------------------------------------------------

    public function test_create_lead_dto_hydrates_required_fields(): void
    {
        $dto = CreateLeadDTO::fromArray([
            'first_name' => 'John',
            'last_name'  => 'Doe',
        ]);

        $this->assertSame('John', $dto->firstName);
        $this->assertSame('Doe', $dto->lastName);
        $this->assertNull($dto->email);
        $this->assertNull($dto->phone);
        $this->assertNull($dto->company);
        $this->assertNull($dto->source);
        $this->assertNull($dto->assignedTo);
        $this->assertNull($dto->campaignId);
        $this->assertNull($dto->notes);
    }

    public function test_create_lead_dto_hydrates_all_fields(): void
    {
        $dto = CreateLeadDTO::fromArray([
            'first_name'  => 'Jane',
            'last_name'   => 'Smith',
            'email'       => 'jane@example.com',
            'phone'       => '+1-555-0100',
            'company'     => 'Acme Corp',
            'source'      => 'website',
            'assigned_to' => 7,
            'campaign_id' => 3,
            'notes'       => 'Interested in enterprise plan',
        ]);

        $this->assertSame('jane@example.com', $dto->email);
        $this->assertSame('+1-555-0100', $dto->phone);
        $this->assertSame('Acme Corp', $dto->company);
        $this->assertSame('website', $dto->source);
        $this->assertSame(7, $dto->assignedTo);
        $this->assertSame(3, $dto->campaignId);
        $this->assertSame('Interested in enterprise plan', $dto->notes);
    }

    public function test_create_lead_dto_ids_cast_to_int(): void
    {
        $dto = CreateLeadDTO::fromArray([
            'first_name'  => 'Bob',
            'last_name'   => 'Jones',
            'assigned_to' => '12',
            'campaign_id' => '5',
        ]);

        $this->assertIsInt($dto->assignedTo);
        $this->assertIsInt($dto->campaignId);
        $this->assertSame(12, $dto->assignedTo);
        $this->assertSame(5, $dto->campaignId);
    }

    // -------------------------------------------------------------------------
    // CreateOpportunityDTO
    // -------------------------------------------------------------------------

    public function test_create_opportunity_dto_hydrates_required_fields(): void
    {
        $dto = CreateOpportunityDTO::fromArray([
            'pipeline_stage_id' => 2,
            'title'             => 'Enterprise Deal',
            'expected_revenue'  => '50000.0000',
            'probability'       => '0.7500',
        ]);

        $this->assertNull($dto->leadId);
        $this->assertSame(2, $dto->pipelineStageId);
        $this->assertSame('Enterprise Deal', $dto->title);
        $this->assertSame('50000.0000', $dto->expectedRevenue);
        $this->assertSame('0.7500', $dto->probability);
        $this->assertNull($dto->closeDate);
        $this->assertNull($dto->assignedTo);
        $this->assertNull($dto->notes);
    }

    public function test_opportunity_revenue_stored_as_string_for_bcmath(): void
    {
        $dto = CreateOpportunityDTO::fromArray([
            'pipeline_stage_id' => 1,
            'title'             => 'BCMath Revenue Test',
            'expected_revenue'  => '9999.9999',
            'probability'       => '0.9999',
        ]);

        $this->assertIsString($dto->expectedRevenue);
        $this->assertIsString($dto->probability);
        $this->assertSame('9999.9999', $dto->expectedRevenue);
        $this->assertSame('0.9999', $dto->probability);
    }

    public function test_opportunity_optional_ids_cast_to_int(): void
    {
        $dto = CreateOpportunityDTO::fromArray([
            'lead_id'           => '8',
            'pipeline_stage_id' => '3',
            'title'             => 'Test Opp',
            'expected_revenue'  => '1000.0000',
            'probability'       => '0.5000',
            'assigned_to'       => '15',
        ]);

        $this->assertIsInt($dto->leadId);
        $this->assertIsInt($dto->pipelineStageId);
        $this->assertIsInt($dto->assignedTo);
        $this->assertSame(8, $dto->leadId);
        $this->assertSame(3, $dto->pipelineStageId);
        $this->assertSame(15, $dto->assignedTo);
    }
}
