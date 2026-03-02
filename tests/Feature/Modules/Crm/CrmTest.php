<?php

declare(strict_types=1);

namespace Tests\Feature\Modules\Crm;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CrmTest extends TestCase
{
    use RefreshDatabase;

    private int $tenantId;

    protected function setUp(): void
    {
        parent::setUp();

        $tenantResponse = $this->postJson('/api/v1/tenants', [
            'name' => 'CRM Test Tenant',
            'slug' => 'crm-test-tenant',
        ]);
        $this->tenantId = $tenantResponse->json('data.id');
    }

    // ─────────────────────────────────────────────
    // Contact tests
    // ─────────────────────────────────────────────

    public function test_can_create_contact(): void
    {
        $response = $this->postJson('/api/v1/crm/contacts', [
            'tenant_id' => $this->tenantId,
            'first_name' => 'John',
            'last_name' => 'Doe',
            'email' => 'john.doe@example.com',
            'phone' => '+1234567890',
            'company' => 'Acme Corp',
            'job_title' => 'Engineer',
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.first_name', 'John')
            ->assertJsonPath('data.last_name', 'Doe')
            ->assertJsonPath('data.email', 'john.doe@example.com')
            ->assertJsonPath('data.phone', '+1234567890')
            ->assertJsonPath('data.company', 'Acme Corp')
            ->assertJsonPath('data.job_title', 'Engineer')
            ->assertJsonPath('data.status', 'active')
            ->assertJsonPath('data.tenant_id', $this->tenantId)
            ->assertJsonStructure(['data' => [
                'id', 'tenant_id', 'first_name', 'last_name', 'full_name',
                'email', 'phone', 'company', 'job_title', 'status', 'notes',
                'created_at', 'updated_at',
            ]]);
    }

    public function test_can_list_contacts(): void
    {
        $this->postJson('/api/v1/crm/contacts', [
            'tenant_id' => $this->tenantId, 'first_name' => 'Alice', 'last_name' => 'Smith',
        ])->assertStatus(201);
        $this->postJson('/api/v1/crm/contacts', [
            'tenant_id' => $this->tenantId, 'first_name' => 'Bob', 'last_name' => 'Jones',
        ])->assertStatus(201);

        $response = $this->getJson("/api/v1/crm/contacts?tenant_id={$this->tenantId}");

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonCount(2, 'data')
            ->assertJsonStructure(['meta' => ['current_page', 'last_page', 'per_page', 'total']]);
    }

    public function test_can_get_contact_by_id(): void
    {
        $created = $this->postJson('/api/v1/crm/contacts', [
            'tenant_id' => $this->tenantId, 'first_name' => 'Jane', 'last_name' => 'Roe',
        ])->json('data');

        $response = $this->getJson("/api/v1/crm/contacts/{$created['id']}?tenant_id={$this->tenantId}");

        $response->assertStatus(200)
            ->assertJsonPath('data.id', $created['id'])
            ->assertJsonPath('data.first_name', 'Jane');
    }

    public function test_returns_404_for_nonexistent_contact(): void
    {
        $response = $this->getJson("/api/v1/crm/contacts/99999?tenant_id={$this->tenantId}");

        $response->assertStatus(404)
            ->assertJsonPath('success', false);
    }

    public function test_can_update_contact(): void
    {
        $created = $this->postJson('/api/v1/crm/contacts', [
            'tenant_id' => $this->tenantId, 'first_name' => 'Jane', 'last_name' => 'Roe',
        ])->json('data');

        $response = $this->putJson("/api/v1/crm/contacts/{$created['id']}?tenant_id={$this->tenantId}", [
            'first_name' => 'Janet',
            'last_name' => 'Roe',
            'status' => 'inactive',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.first_name', 'Janet')
            ->assertJsonPath('data.status', 'inactive');
    }

    public function test_can_delete_contact(): void
    {
        $created = $this->postJson('/api/v1/crm/contacts', [
            'tenant_id' => $this->tenantId, 'first_name' => 'Delete', 'last_name' => 'Me',
        ])->json('data');

        $this->deleteJson("/api/v1/crm/contacts/{$created['id']}?tenant_id={$this->tenantId}")
            ->assertStatus(200)
            ->assertJsonPath('success', true);

        $this->getJson("/api/v1/crm/contacts/{$created['id']}?tenant_id={$this->tenantId}")
            ->assertStatus(404);
    }

    // ─────────────────────────────────────────────
    // Lead tests
    // ─────────────────────────────────────────────

    public function test_can_create_lead(): void
    {
        $response = $this->postJson('/api/v1/crm/leads', [
            'tenant_id' => $this->tenantId,
            'title' => 'Big Deal',
            'description' => 'A huge opportunity',
            'estimated_value' => '50000',
            'currency' => 'LKR',
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.title', 'Big Deal')
            ->assertJsonPath('data.tenant_id', $this->tenantId)
            ->assertJsonStructure(['data' => [
                'id', 'tenant_id', 'contact_id', 'title', 'description',
                'status', 'estimated_value', 'currency', 'expected_close_date',
                'notes', 'created_at', 'updated_at',
            ]]);
    }

    public function test_new_lead_has_new_status(): void
    {
        $response = $this->postJson('/api/v1/crm/leads', [
            'tenant_id' => $this->tenantId,
            'title' => 'Status Test Lead',
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.status', 'new');
    }

    public function test_lead_default_currency_is_lkr(): void
    {
        $response = $this->postJson('/api/v1/crm/leads', [
            'tenant_id' => $this->tenantId,
            'title' => 'Currency Test Lead',
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.currency', 'LKR');
    }

    public function test_can_list_leads(): void
    {
        $this->postJson('/api/v1/crm/leads', [
            'tenant_id' => $this->tenantId, 'title' => 'Lead One',
        ])->assertStatus(201);
        $this->postJson('/api/v1/crm/leads', [
            'tenant_id' => $this->tenantId, 'title' => 'Lead Two',
        ])->assertStatus(201);

        $response = $this->getJson("/api/v1/crm/leads?tenant_id={$this->tenantId}");

        $response->assertStatus(200)
            ->assertJsonCount(2, 'data');
    }

    public function test_can_get_lead_by_id(): void
    {
        $created = $this->postJson('/api/v1/crm/leads', [
            'tenant_id' => $this->tenantId, 'title' => 'Find Me Lead',
        ])->json('data');

        $response = $this->getJson("/api/v1/crm/leads/{$created['id']}?tenant_id={$this->tenantId}");

        $response->assertStatus(200)
            ->assertJsonPath('data.id', $created['id'])
            ->assertJsonPath('data.title', 'Find Me Lead');
    }

    public function test_returns_404_for_nonexistent_lead(): void
    {
        $response = $this->getJson("/api/v1/crm/leads/99999?tenant_id={$this->tenantId}");

        $response->assertStatus(404)
            ->assertJsonPath('success', false);
    }

    public function test_can_update_lead(): void
    {
        $created = $this->postJson('/api/v1/crm/leads', [
            'tenant_id' => $this->tenantId, 'title' => 'Update Me',
        ])->json('data');

        $response = $this->putJson("/api/v1/crm/leads/{$created['id']}?tenant_id={$this->tenantId}", [
            'status' => 'qualified',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.status', 'qualified');
    }

    public function test_can_delete_lead(): void
    {
        $created = $this->postJson('/api/v1/crm/leads', [
            'tenant_id' => $this->tenantId, 'title' => 'Delete This Lead',
        ])->json('data');

        $this->deleteJson("/api/v1/crm/leads/{$created['id']}?tenant_id={$this->tenantId}")
            ->assertStatus(200)
            ->assertJsonPath('success', true);

        $this->getJson("/api/v1/crm/leads/{$created['id']}?tenant_id={$this->tenantId}")
            ->assertStatus(404);
    }

    public function test_can_create_lead_linked_to_contact(): void
    {
        $contact = $this->postJson('/api/v1/crm/contacts', [
            'tenant_id' => $this->tenantId, 'first_name' => 'Lead', 'last_name' => 'Contact',
        ])->json('data');

        $response = $this->postJson('/api/v1/crm/leads', [
            'tenant_id' => $this->tenantId,
            'title' => 'Linked Lead',
            'contact_id' => $contact['id'],
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.contact_id', $contact['id']);
    }

    public function test_can_list_contact_leads(): void
    {
        $contact = $this->postJson('/api/v1/crm/contacts', [
            'tenant_id' => $this->tenantId, 'first_name' => 'Multi', 'last_name' => 'Lead',
        ])->json('data');

        $this->postJson('/api/v1/crm/leads', [
            'tenant_id' => $this->tenantId, 'title' => 'Lead A', 'contact_id' => $contact['id'],
        ])->assertStatus(201);
        $this->postJson('/api/v1/crm/leads', [
            'tenant_id' => $this->tenantId, 'title' => 'Lead B', 'contact_id' => $contact['id'],
        ])->assertStatus(201);

        $response = $this->getJson("/api/v1/crm/contacts/{$contact['id']}/leads?tenant_id={$this->tenantId}");

        $response->assertStatus(200)
            ->assertJsonCount(2, 'data');
    }

    // ─────────────────────────────────────────────
    // Activity tests
    // ─────────────────────────────────────────────

    public function test_can_log_activity(): void
    {
        $response = $this->postJson('/api/v1/crm/activities', [
            'tenant_id' => $this->tenantId,
            'type' => 'call',
            'subject' => 'Initial call',
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.type', 'call')
            ->assertJsonPath('data.subject', 'Initial call')
            ->assertJsonStructure(['data' => [
                'id', 'tenant_id', 'contact_id', 'lead_id', 'type',
                'subject', 'description', 'scheduled_at', 'completed_at',
                'created_at', 'updated_at',
            ]]);
    }

    public function test_can_log_activity_for_contact(): void
    {
        $contact = $this->postJson('/api/v1/crm/contacts', [
            'tenant_id' => $this->tenantId, 'first_name' => 'Act', 'last_name' => 'Contact',
        ])->json('data');

        $response = $this->postJson('/api/v1/crm/activities', [
            'tenant_id' => $this->tenantId,
            'type' => 'email',
            'subject' => 'Follow-up email',
            'contact_id' => $contact['id'],
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.contact_id', $contact['id']);
    }

    public function test_can_log_activity_for_lead(): void
    {
        $lead = $this->postJson('/api/v1/crm/leads', [
            'tenant_id' => $this->tenantId, 'title' => 'Activity Lead',
        ])->json('data');

        $response = $this->postJson('/api/v1/crm/activities', [
            'tenant_id' => $this->tenantId,
            'type' => 'meeting',
            'subject' => 'Demo meeting',
            'lead_id' => $lead['id'],
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.lead_id', $lead['id']);
    }

    public function test_can_list_activities(): void
    {
        $this->postJson('/api/v1/crm/activities', [
            'tenant_id' => $this->tenantId, 'type' => 'note', 'subject' => 'Note One',
        ])->assertStatus(201);
        $this->postJson('/api/v1/crm/activities', [
            'tenant_id' => $this->tenantId, 'type' => 'task', 'subject' => 'Task One',
        ])->assertStatus(201);

        $response = $this->getJson("/api/v1/crm/activities?tenant_id={$this->tenantId}");

        $response->assertStatus(200)
            ->assertJsonCount(2, 'data');
    }

    public function test_can_get_activity_by_id(): void
    {
        $created = $this->postJson('/api/v1/crm/activities', [
            'tenant_id' => $this->tenantId, 'type' => 'call', 'subject' => 'Find Me Activity',
        ])->json('data');

        $response = $this->getJson("/api/v1/crm/activities/{$created['id']}?tenant_id={$this->tenantId}");

        $response->assertStatus(200)
            ->assertJsonPath('data.id', $created['id'])
            ->assertJsonPath('data.subject', 'Find Me Activity');
    }

    public function test_returns_404_for_nonexistent_activity(): void
    {
        $response = $this->getJson("/api/v1/crm/activities/99999?tenant_id={$this->tenantId}");

        $response->assertStatus(404)
            ->assertJsonPath('success', false);
    }

    public function test_can_delete_activity(): void
    {
        $created = $this->postJson('/api/v1/crm/activities', [
            'tenant_id' => $this->tenantId, 'type' => 'call', 'subject' => 'Delete Me Activity',
        ])->json('data');

        $this->deleteJson("/api/v1/crm/activities/{$created['id']}?tenant_id={$this->tenantId}")
            ->assertStatus(200)
            ->assertJsonPath('success', true);

        $this->getJson("/api/v1/crm/activities/{$created['id']}?tenant_id={$this->tenantId}")
            ->assertStatus(404);
    }

    public function test_can_list_contact_activities(): void
    {
        $contact = $this->postJson('/api/v1/crm/contacts', [
            'tenant_id' => $this->tenantId, 'first_name' => 'Contact', 'last_name' => 'Acts',
        ])->json('data');

        $this->postJson('/api/v1/crm/activities', [
            'tenant_id' => $this->tenantId, 'type' => 'call', 'subject' => 'Call 1',
            'contact_id' => $contact['id'],
        ])->assertStatus(201);
        $this->postJson('/api/v1/crm/activities', [
            'tenant_id' => $this->tenantId, 'type' => 'email', 'subject' => 'Email 1',
            'contact_id' => $contact['id'],
        ])->assertStatus(201);

        $response = $this->getJson("/api/v1/crm/contacts/{$contact['id']}/activities?tenant_id={$this->tenantId}");

        $response->assertStatus(200)
            ->assertJsonCount(2, 'data');
    }

    public function test_can_list_lead_activities(): void
    {
        $lead = $this->postJson('/api/v1/crm/leads', [
            'tenant_id' => $this->tenantId, 'title' => 'Lead For Activities',
        ])->json('data');

        $this->postJson('/api/v1/crm/activities', [
            'tenant_id' => $this->tenantId, 'type' => 'meeting', 'subject' => 'Meeting 1',
            'lead_id' => $lead['id'],
        ])->assertStatus(201);
        $this->postJson('/api/v1/crm/activities', [
            'tenant_id' => $this->tenantId, 'type' => 'note', 'subject' => 'Note 1',
            'lead_id' => $lead['id'],
        ])->assertStatus(201);

        $response = $this->getJson("/api/v1/crm/leads/{$lead['id']}/activities?tenant_id={$this->tenantId}");

        $response->assertStatus(200)
            ->assertJsonCount(2, 'data');
    }

    public function test_activity_type_validation(): void
    {
        $response = $this->postJson('/api/v1/crm/activities', [
            'tenant_id' => $this->tenantId,
            'type' => 'invalid_type',
            'subject' => 'Test',
        ]);

        $response->assertStatus(422);
    }

    public function test_contact_full_name_computed(): void
    {
        $response = $this->postJson('/api/v1/crm/contacts', [
            'tenant_id' => $this->tenantId,
            'first_name' => 'John',
            'last_name' => 'Smith',
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.full_name', 'John Smith');
    }

    public function test_lead_currency_must_be_supported(): void
    {
        $response = $this->postJson('/api/v1/crm/leads', [
            'tenant_id' => $this->tenantId,
            'title' => 'Invalid Currency Lead',
            'currency' => 'XYZ',
        ]);

        $response->assertStatus(422);
    }
}
