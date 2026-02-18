<?php

declare(strict_types=1);

namespace Modules\Sales\Tests\Unit;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Sales\Entities\Customer;
use Modules\Sales\Entities\Lead;
use Tests\TestCase;

class LeadTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_creates_lead_successfully(): void
    {
        $lead = Lead::factory()->create([
            'title' => 'Test Lead',
            'contact_name' => 'John Doe',
            'contact_email' => 'john@example.com',
            'status' => 'new',
        ]);

        $this->assertDatabaseHas('leads', [
            'id' => $lead->id,
            'title' => 'Test Lead',
            'contact_name' => 'John Doe',
            'contact_email' => 'john@example.com',
        ]);
    }

    public function test_it_generates_lead_number_automatically(): void
    {
        $lead = Lead::factory()->create();

        $this->assertNotNull($lead->lead_number);
        $this->assertStringStartsWith('LEAD-', $lead->lead_number);
    }

    public function test_it_has_qualified_scope(): void
    {
        Lead::factory()->create(['status' => 'qualified']);
        Lead::factory()->create(['status' => 'new']);
        Lead::factory()->create(['status' => 'qualified']);

        $qualifiedLeads = Lead::qualified()->get();

        $this->assertCount(2, $qualifiedLeads);
    }

    public function test_it_has_new_scope(): void
    {
        Lead::factory()->create(['status' => 'new']);
        Lead::factory()->create(['status' => 'contacted']);
        Lead::factory()->create(['status' => 'new']);

        $newLeads = Lead::new()->get();

        $this->assertCount(2, $newLeads);
    }

    public function test_it_has_won_scope(): void
    {
        Lead::factory()->create(['status' => 'won']);
        Lead::factory()->create(['status' => 'lost']);
        Lead::factory()->create(['status' => 'won']);

        $wonLeads = Lead::won()->get();

        $this->assertCount(2, $wonLeads);
    }

    public function test_it_has_lost_scope(): void
    {
        Lead::factory()->create(['status' => 'lost']);
        Lead::factory()->create(['status' => 'won']);
        Lead::factory()->create(['status' => 'lost']);

        $lostLeads = Lead::lost()->get();

        $this->assertCount(2, $lostLeads);
    }

    public function test_it_belongs_to_customer(): void
    {
        $customer = Customer::factory()->create();
        $lead = Lead::factory()->create(['customer_id' => $customer->id]);

        $this->assertInstanceOf(Customer::class, $lead->customer);
        $this->assertEquals($customer->id, $lead->customer->id);
    }

    public function test_it_casts_tags_to_array(): void
    {
        $lead = Lead::factory()->create([
            'tags' => ['vip', 'urgent', 'enterprise'],
        ]);

        $this->assertIsArray($lead->tags);
        $this->assertEquals(['vip', 'urgent', 'enterprise'], $lead->tags);
    }

    public function test_it_soft_deletes(): void
    {
        $lead = Lead::factory()->create();
        $leadId = $lead->id;

        $lead->delete();

        $this->assertSoftDeleted('leads', ['id' => $leadId]);
        $this->assertNotNull(Lead::withTrashed()->find($leadId)->deleted_at);
    }

    public function test_it_can_restore_soft_deleted_lead(): void
    {
        $lead = Lead::factory()->create();
        $leadId = $lead->id;

        $lead->delete();
        $this->assertSoftDeleted('leads', ['id' => $leadId]);

        Lead::withTrashed()->find($leadId)->restore();
        $this->assertDatabaseHas('leads', ['id' => $leadId, 'deleted_at' => null]);
    }

    public function test_it_has_auditable_fields(): void
    {
        $lead = Lead::factory()->create();

        $this->assertNotNull($lead->created_at);
        $this->assertNotNull($lead->updated_at);
    }

    public function test_it_calculates_probability_correctly(): void
    {
        $lead = Lead::factory()->create([
            'probability' => 75,
            'expected_revenue' => 10000,
        ]);

        $this->assertEquals(75, $lead->probability);
        $this->assertEquals(10000, $lead->expected_revenue);
    }
}
