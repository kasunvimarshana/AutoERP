<?php

declare(strict_types=1);

namespace Modules\Sales\Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Sales\Entities\Lead;
use Tests\TestCase;

class LeadApiTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create([
            'permissions' => [
                'lead.view',
                'lead.create',
                'lead.update',
                'lead.delete',
                'lead.convert-to-customer',
            ],
        ]);
    }

    public function test_it_lists_leads(): void
    {
        Lead::factory()->count(3)->create();

        $response = $this->actingAs($this->user)
            ->getJson('/api/v1/sales/leads');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'lead_number',
                        'title',
                        'contact_name',
                        'contact_email',
                        'status',
                        'created_at',
                    ],
                ],
            ]);
    }

    public function test_it_creates_lead(): void
    {
        $data = [
            'title' => 'New Business Opportunity',
            'contact_name' => 'Jane Smith',
            'contact_email' => 'jane@example.com',
            'contact_phone' => '+1234567890',
            'company' => 'Acme Corp',
            'status' => 'new',
            'stage' => 'initial',
            'probability' => 25,
            'expected_revenue' => 50000,
        ];

        $response = $this->actingAs($this->user)
            ->postJson('/api/v1/sales/leads', $data);

        $response->assertStatus(201)
            ->assertJsonPath('data.title', 'New Business Opportunity')
            ->assertJsonPath('data.contact_name', 'Jane Smith');

        $this->assertDatabaseHas('leads', [
            'title' => 'New Business Opportunity',
            'contact_email' => 'jane@example.com',
        ]);
    }

    public function test_it_shows_lead(): void
    {
        $lead = Lead::factory()->create();

        $response = $this->actingAs($this->user)
            ->getJson("/api/v1/sales/leads/{$lead->id}");

        $response->assertStatus(200)
            ->assertJsonPath('data.id', $lead->id)
            ->assertJsonPath('data.title', $lead->title);
    }

    public function test_it_updates_lead(): void
    {
        $lead = Lead::factory()->create();

        $data = [
            'title' => 'Updated Lead Title',
            'status' => 'contacted',
        ];

        $response = $this->actingAs($this->user)
            ->putJson("/api/v1/sales/leads/{$lead->id}", $data);

        $response->assertStatus(200)
            ->assertJsonPath('data.title', 'Updated Lead Title');

        $this->assertDatabaseHas('leads', [
            'id' => $lead->id,
            'title' => 'Updated Lead Title',
            'status' => 'contacted',
        ]);
    }

    public function test_it_deletes_lead(): void
    {
        $lead = Lead::factory()->create();

        $response = $this->actingAs($this->user)
            ->deleteJson("/api/v1/sales/leads/{$lead->id}");

        $response->assertStatus(204);

        $this->assertSoftDeleted('leads', [
            'id' => $lead->id,
        ]);
    }

    public function test_it_converts_lead_to_customer(): void
    {
        $lead = Lead::factory()->create([
            'status' => 'qualified',
            'contact_name' => 'John Doe',
            'contact_email' => 'john@example.com',
            'company' => 'Test Company',
        ]);

        $response = $this->actingAs($this->user)
            ->postJson("/api/v1/sales/leads/{$lead->id}/convert");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'customer' => ['id', 'name', 'email'],
                ],
            ]);

        $this->assertDatabaseHas('customers', [
            'email' => 'john@example.com',
        ]);

        $lead->refresh();
        $this->assertEquals('converted', $lead->status);
        $this->assertNotNull($lead->customer_id);
    }

    public function test_it_filters_qualified_leads(): void
    {
        Lead::factory()->create(['status' => 'qualified']);
        Lead::factory()->create(['status' => 'new']);
        Lead::factory()->create(['status' => 'qualified']);

        $response = $this->actingAs($this->user)
            ->getJson('/api/v1/sales/leads?filter[status]=qualified');

        $response->assertStatus(200);

        $data = $response->json('data');
        $this->assertCount(2, $data);
    }

    public function test_it_searches_leads_by_contact_name(): void
    {
        Lead::factory()->create(['contact_name' => 'John Doe']);
        Lead::factory()->create(['contact_name' => 'Jane Smith']);

        $response = $this->actingAs($this->user)
            ->getJson('/api/v1/sales/leads?search=John');

        $response->assertStatus(200);

        $data = $response->json('data');
        $this->assertCount(1, $data);
        $this->assertEquals('John Doe', $data[0]['contact_name']);
    }

    public function test_unauthorized_user_cannot_create_lead(): void
    {
        $unauthorizedUser = User::factory()->create([
            'permissions' => [],
        ]);

        $response = $this->actingAs($unauthorizedUser)
            ->postJson('/api/v1/sales/leads', [
                'title' => 'Test',
                'contact_name' => 'Test',
                'contact_email' => 'test@example.com',
            ]);

        $response->assertStatus(403);
    }

    public function test_it_validates_required_fields(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson('/api/v1/sales/leads', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['title', 'contact_name', 'contact_email']);
    }

    public function test_it_validates_email_format(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson('/api/v1/sales/leads', [
                'title' => 'Test',
                'contact_name' => 'Test',
                'contact_email' => 'invalid-email',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['contact_email']);
    }
}
