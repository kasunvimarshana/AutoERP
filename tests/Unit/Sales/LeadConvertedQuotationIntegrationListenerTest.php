<?php

namespace Tests\Unit\Sales;

use DomainException;
use Mockery;
use Modules\CRM\Domain\Events\LeadConverted;
use Modules\Sales\Application\Listeners\HandleLeadConvertedListener;
use Modules\Sales\Application\UseCases\CreateQuotationUseCase;
use PHPUnit\Framework\TestCase;

class LeadConvertedQuotationIntegrationListenerTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    private function makeEvent(array $overrides = []): LeadConverted
    {
        return new LeadConverted(
            leadId:          array_key_exists('leadId', $overrides)          ? $overrides['leadId']          : 'lead-1',
            opportunityId:   array_key_exists('opportunityId', $overrides)   ? $overrides['opportunityId']   : 'opp-1',
            tenantId:        array_key_exists('tenantId', $overrides)        ? $overrides['tenantId']        : 'tenant-1',
            contactName:     array_key_exists('contactName', $overrides)     ? $overrides['contactName']     : 'Acme Corp',
            contactEmail:    array_key_exists('contactEmail', $overrides)    ? $overrides['contactEmail']    : 'contact@acme.com',
            expectedRevenue: array_key_exists('expectedRevenue', $overrides) ? $overrides['expectedRevenue'] : '5000.00',
        );
    }

    // -------------------------------------------------------------------------
    // Guard: skip when tenantId is empty
    // -------------------------------------------------------------------------

    public function test_skips_when_tenant_id_empty(): void
    {
        $useCase = Mockery::mock(CreateQuotationUseCase::class);
        $useCase->shouldNotReceive('execute');

        (new HandleLeadConvertedListener($useCase))->handle($this->makeEvent(['tenantId' => '']));

        $this->addToAssertionCount(1);
    }

    // -------------------------------------------------------------------------
    // Guard: skip when contactName is empty
    // -------------------------------------------------------------------------

    public function test_skips_when_contact_name_empty(): void
    {
        $useCase = Mockery::mock(CreateQuotationUseCase::class);
        $useCase->shouldNotReceive('execute');

        (new HandleLeadConvertedListener($useCase))->handle($this->makeEvent(['contactName' => '']));

        $this->addToAssertionCount(1);
    }

    // -------------------------------------------------------------------------
    // Successful quotation creation
    // -------------------------------------------------------------------------

    public function test_creates_quotation_with_correct_tenant_id(): void
    {
        $useCase = Mockery::mock(CreateQuotationUseCase::class);
        $useCase->shouldReceive('execute')
            ->once()
            ->with(Mockery::on(fn ($d) => $d['tenant_id'] === 'tenant-1'))
            ->andReturn((object) ['id' => 'quot-1']);

        (new HandleLeadConvertedListener($useCase))->handle($this->makeEvent());

        $this->addToAssertionCount(1);
    }

    public function test_creates_quotation_with_null_customer_id(): void
    {
        $useCase = Mockery::mock(CreateQuotationUseCase::class);
        $useCase->shouldReceive('execute')
            ->once()
            ->with(Mockery::on(fn ($d) => array_key_exists('customer_id', $d) && $d['customer_id'] === null))
            ->andReturn((object) ['id' => 'quot-1']);

        (new HandleLeadConvertedListener($useCase))->handle($this->makeEvent());

        $this->addToAssertionCount(1);
    }

    public function test_creates_quotation_with_empty_lines(): void
    {
        $useCase = Mockery::mock(CreateQuotationUseCase::class);
        $useCase->shouldReceive('execute')
            ->once()
            ->with(Mockery::on(fn ($d) => isset($d['lines']) && $d['lines'] === []))
            ->andReturn((object) ['id' => 'quot-1']);

        (new HandleLeadConvertedListener($useCase))->handle($this->makeEvent());

        $this->addToAssertionCount(1);
    }

    public function test_notes_reference_lead_id(): void
    {
        $useCase = Mockery::mock(CreateQuotationUseCase::class);
        $useCase->shouldReceive('execute')
            ->once()
            ->with(Mockery::on(fn ($d) => str_contains($d['notes'], 'lead-1')))
            ->andReturn((object) ['id' => 'quot-1']);

        (new HandleLeadConvertedListener($useCase))->handle($this->makeEvent(['leadId' => 'lead-1']));

        $this->addToAssertionCount(1);
    }

    public function test_notes_reference_opportunity_id(): void
    {
        $useCase = Mockery::mock(CreateQuotationUseCase::class);
        $useCase->shouldReceive('execute')
            ->once()
            ->with(Mockery::on(fn ($d) => str_contains($d['notes'], 'opp-42')))
            ->andReturn((object) ['id' => 'quot-1']);

        (new HandleLeadConvertedListener($useCase))->handle($this->makeEvent(['opportunityId' => 'opp-42']));

        $this->addToAssertionCount(1);
    }

    public function test_notes_include_contact_email_when_present(): void
    {
        $useCase = Mockery::mock(CreateQuotationUseCase::class);
        $useCase->shouldReceive('execute')
            ->once()
            ->with(Mockery::on(fn ($d) => str_contains($d['notes'], 'contact@acme.com')))
            ->andReturn((object) ['id' => 'quot-1']);

        (new HandleLeadConvertedListener($useCase))->handle($this->makeEvent(['contactEmail' => 'contact@acme.com']));

        $this->addToAssertionCount(1);
    }

    public function test_notes_omit_email_when_contact_email_empty(): void
    {
        $useCase = Mockery::mock(CreateQuotationUseCase::class);
        $useCase->shouldReceive('execute')
            ->once()
            ->with(Mockery::on(fn ($d) => !str_contains($d['notes'], 'contact@acme.com')))
            ->andReturn((object) ['id' => 'quot-1']);

        (new HandleLeadConvertedListener($useCase))->handle($this->makeEvent(['contactEmail' => '']));

        $this->addToAssertionCount(1);
    }

    // -------------------------------------------------------------------------
    // Graceful degradation
    // -------------------------------------------------------------------------

    public function test_graceful_degradation_when_create_quotation_throws_domain_exception(): void
    {
        $useCase = Mockery::mock(CreateQuotationUseCase::class);
        $useCase->shouldReceive('execute')->once()->andThrow(new DomainException('quota exceeded'));

        // Must not throw — lead conversion must not be rolled back
        (new HandleLeadConvertedListener($useCase))->handle($this->makeEvent());

        $this->addToAssertionCount(1);
    }

    public function test_graceful_degradation_when_create_quotation_throws_runtime_exception(): void
    {
        $useCase = Mockery::mock(CreateQuotationUseCase::class);
        $useCase->shouldReceive('execute')->once()->andThrow(new \RuntimeException('DB error'));

        (new HandleLeadConvertedListener($useCase))->handle($this->makeEvent());

        $this->addToAssertionCount(1);
    }

    // -------------------------------------------------------------------------
    // LeadConverted event enrichment
    // -------------------------------------------------------------------------

    public function test_lead_converted_event_carries_enriched_fields(): void
    {
        $event = new LeadConverted(
            leadId:          'lead-x',
            opportunityId:   'opp-x',
            tenantId:        'tenant-x',
            contactName:     'Jane Doe',
            contactEmail:    'jane@example.com',
            expectedRevenue: '12000.00',
        );

        $this->assertSame('lead-x',         $event->leadId);
        $this->assertSame('opp-x',          $event->opportunityId);
        $this->assertSame('tenant-x',       $event->tenantId);
        $this->assertSame('Jane Doe',       $event->contactName);
        $this->assertSame('jane@example.com', $event->contactEmail);
        $this->assertSame('12000.00',       $event->expectedRevenue);
    }

    public function test_lead_converted_event_defaults_enriched_fields_to_empty_strings(): void
    {
        // Backwards-compatible: old callers pass only leadId and opportunityId
        $event = new LeadConverted('lead-old', 'opp-old');

        $this->assertSame('',  $event->tenantId);
        $this->assertSame('',  $event->contactName);
        $this->assertSame('',  $event->contactEmail);
        $this->assertSame('0', $event->expectedRevenue);
    }
}
