<?php

declare(strict_types=1);

namespace Tests\Feature\Modules\Accounting;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AccountingTest extends TestCase
{
    use RefreshDatabase;

    private int $tenantId;

    private array $defaultAccount;

    protected function setUp(): void
    {
        parent::setUp();

        $tenantResponse = $this->postJson('/api/v1/tenants', [
            'name' => 'Accounting Test Tenant',
            'slug' => 'accounting-test-tenant',
        ]);
        $this->tenantId = $tenantResponse->json('data.id');

        $this->defaultAccount = [
            'tenant_id' => $this->tenantId,
            'code' => '1000',
            'name' => 'Cash',
            'type' => 'asset',
            'description' => 'Cash and cash equivalents',
        ];
    }

    // ─────────────────────────────────────────────
    // Account tests
    // ─────────────────────────────────────────────

    public function test_can_create_account(): void
    {
        $response = $this->postJson('/api/v1/accounts', $this->defaultAccount);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'id', 'tenant_id', 'code', 'name', 'type', 'status',
                    'description', 'is_system_account', 'opening_balance', 'current_balance',
                ],
                'errors',
            ])
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.code', '1000')
            ->assertJsonPath('data.name', 'Cash')
            ->assertJsonPath('data.type', 'asset')
            ->assertJsonPath('data.status', 'active')
            ->assertJsonPath('data.tenant_id', $this->tenantId);
    }

    public function test_account_code_must_be_unique_per_tenant(): void
    {
        $this->postJson('/api/v1/accounts', $this->defaultAccount)->assertStatus(201);

        $response = $this->postJson('/api/v1/accounts', $this->defaultAccount);

        $response->assertStatus(422)
            ->assertJsonPath('success', false);
    }

    public function test_can_list_accounts(): void
    {
        $this->postJson('/api/v1/accounts', $this->defaultAccount)->assertStatus(201);
        $this->postJson('/api/v1/accounts', array_merge($this->defaultAccount, [
            'code' => '2000',
            'name' => 'Accounts Payable',
            'type' => 'liability',
        ]))->assertStatus(201);

        $response = $this->getJson("/api/v1/accounts?tenant_id={$this->tenantId}");

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonStructure([
                'success', 'message', 'data', 'errors',
                'meta' => ['current_page', 'last_page', 'per_page', 'total'],
            ]);

        $this->assertCount(2, $response->json('data'));
    }

    public function test_can_get_account_by_id(): void
    {
        $createResponse = $this->postJson('/api/v1/accounts', $this->defaultAccount);
        $id = $createResponse->json('data.id');

        $response = $this->getJson("/api/v1/accounts/{$id}?tenant_id={$this->tenantId}");

        $response->assertStatus(200)
            ->assertJsonPath('data.id', $id)
            ->assertJsonPath('data.code', '1000')
            ->assertJsonPath('data.name', 'Cash');
    }

    public function test_returns_404_for_nonexistent_account(): void
    {
        $response = $this->getJson("/api/v1/accounts/99999?tenant_id={$this->tenantId}");

        $response->assertStatus(404)
            ->assertJsonPath('success', false);
    }

    public function test_can_update_account(): void
    {
        $createResponse = $this->postJson('/api/v1/accounts', $this->defaultAccount);
        $id = $createResponse->json('data.id');

        $response = $this->putJson("/api/v1/accounts/{$id}?tenant_id={$this->tenantId}", [
            'name' => 'Cash and Bank',
            'description' => 'Updated description',
            'status' => 'active',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.name', 'Cash and Bank')
            ->assertJsonPath('data.description', 'Updated description');
    }

    public function test_can_delete_account(): void
    {
        $createResponse = $this->postJson('/api/v1/accounts', $this->defaultAccount);
        $id = $createResponse->json('data.id');

        $response = $this->deleteJson("/api/v1/accounts/{$id}?tenant_id={$this->tenantId}");

        $response->assertStatus(200)
            ->assertJsonPath('success', true);

        $this->getJson("/api/v1/accounts/{$id}?tenant_id={$this->tenantId}")
            ->assertStatus(404);
    }

    // ─────────────────────────────────────────────
    // Journal Entry tests
    // ─────────────────────────────────────────────

    private function createAccount(string $code, string $name, string $type): int
    {
        $response = $this->postJson('/api/v1/accounts', [
            'tenant_id' => $this->tenantId,
            'code' => $code,
            'name' => $name,
            'type' => $type,
        ]);

        return (int) $response->json('data.id');
    }

    private function defaultJournalEntryPayload(int $debitAccountId, int $creditAccountId): array
    {
        return [
            'tenant_id' => $this->tenantId,
            'entry_date' => '2026-01-15',
            'currency' => 'LKR',
            'description' => 'Test journal entry',
            'lines' => [
                [
                    'account_id' => $debitAccountId,
                    'debit_amount' => '500.0000',
                    'credit_amount' => '0.0000',
                    'description' => 'Debit line',
                ],
                [
                    'account_id' => $creditAccountId,
                    'debit_amount' => '0.0000',
                    'credit_amount' => '500.0000',
                    'description' => 'Credit line',
                ],
            ],
        ];
    }

    public function test_can_create_journal_entry(): void
    {
        $expenseId = $this->createAccount('5000', 'Office Expense', 'expense');
        $assetId = $this->createAccount('1000', 'Cash', 'asset');

        $payload = $this->defaultJournalEntryPayload($expenseId, $assetId);
        $response = $this->postJson('/api/v1/accounting/journal-entries', $payload);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'id', 'tenant_id', 'entry_number', 'entry_date', 'currency',
                    'status', 'total_debit', 'total_credit', 'is_balanced',
                    'lines' => [['id', 'account_id', 'debit_amount', 'credit_amount']],
                ],
                'errors',
            ])
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.status', 'draft')
            ->assertJsonPath('data.is_balanced', true)
            ->assertJsonPath('data.tenant_id', $this->tenantId);
    }

    public function test_journal_entry_must_be_balanced(): void
    {
        $expenseId = $this->createAccount('5000', 'Office Expense', 'expense');
        $assetId = $this->createAccount('1000', 'Cash', 'asset');

        $response = $this->postJson('/api/v1/accounting/journal-entries', [
            'tenant_id' => $this->tenantId,
            'entry_date' => '2026-01-15',
            'currency' => 'LKR',
            'lines' => [
                [
                    'account_id' => $expenseId,
                    'debit_amount' => '500.0000',
                    'credit_amount' => '0.0000',
                ],
                [
                    'account_id' => $assetId,
                    'debit_amount' => '0.0000',
                    'credit_amount' => '400.0000',
                ],
            ],
        ]);

        $response->assertStatus(422)
            ->assertJsonPath('success', false);
    }

    public function test_journal_entry_requires_at_least_two_lines(): void
    {
        $assetId = $this->createAccount('1000', 'Cash', 'asset');

        $response = $this->postJson('/api/v1/accounting/journal-entries', [
            'tenant_id' => $this->tenantId,
            'entry_date' => '2026-01-15',
            'currency' => 'LKR',
            'lines' => [
                [
                    'account_id' => $assetId,
                    'debit_amount' => '500.0000',
                    'credit_amount' => '0.0000',
                ],
            ],
        ]);

        $response->assertStatus(422);
    }

    public function test_can_list_journal_entries(): void
    {
        $expenseId = $this->createAccount('5000', 'Office Expense', 'expense');
        $assetId = $this->createAccount('1000', 'Cash', 'asset');

        $this->postJson('/api/v1/accounting/journal-entries', $this->defaultJournalEntryPayload($expenseId, $assetId))
            ->assertStatus(201);

        $response = $this->getJson("/api/v1/accounting/journal-entries?tenant_id={$this->tenantId}");

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonStructure([
                'success', 'message', 'data', 'errors',
                'meta' => ['current_page', 'last_page', 'per_page', 'total'],
            ]);

        $this->assertCount(1, $response->json('data'));
    }

    public function test_can_get_journal_entry_by_id(): void
    {
        $expenseId = $this->createAccount('5000', 'Office Expense', 'expense');
        $assetId = $this->createAccount('1000', 'Cash', 'asset');
        $createResponse = $this->postJson('/api/v1/accounting/journal-entries', $this->defaultJournalEntryPayload($expenseId, $assetId));
        $id = $createResponse->json('data.id');

        $response = $this->getJson("/api/v1/accounting/journal-entries/{$id}?tenant_id={$this->tenantId}");

        $response->assertStatus(200)
            ->assertJsonPath('data.id', $id);
    }

    public function test_returns_404_for_nonexistent_journal_entry(): void
    {
        $response = $this->getJson("/api/v1/accounting/journal-entries/99999?tenant_id={$this->tenantId}");

        $response->assertStatus(404)
            ->assertJsonPath('success', false);
    }

    public function test_can_post_journal_entry(): void
    {
        $expenseId = $this->createAccount('5000', 'Office Expense', 'expense');
        $assetId = $this->createAccount('1000', 'Cash', 'asset');
        $createResponse = $this->postJson('/api/v1/accounting/journal-entries', $this->defaultJournalEntryPayload($expenseId, $assetId));
        $id = $createResponse->json('data.id');

        $response = $this->postJson("/api/v1/accounting/journal-entries/{$id}/post?tenant_id={$this->tenantId}");

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.status', 'posted');
    }

    public function test_cannot_post_already_posted_entry(): void
    {
        $expenseId = $this->createAccount('5000', 'Office Expense', 'expense');
        $assetId = $this->createAccount('1000', 'Cash', 'asset');
        $createResponse = $this->postJson('/api/v1/accounting/journal-entries', $this->defaultJournalEntryPayload($expenseId, $assetId));
        $id = $createResponse->json('data.id');

        $this->postJson("/api/v1/accounting/journal-entries/{$id}/post?tenant_id={$this->tenantId}")->assertStatus(200);

        $response = $this->postJson("/api/v1/accounting/journal-entries/{$id}/post?tenant_id={$this->tenantId}");

        $response->assertStatus(422)
            ->assertJsonPath('success', false);
    }

    public function test_can_delete_draft_journal_entry(): void
    {
        $expenseId = $this->createAccount('5000', 'Office Expense', 'expense');
        $assetId = $this->createAccount('1000', 'Cash', 'asset');
        $createResponse = $this->postJson('/api/v1/accounting/journal-entries', $this->defaultJournalEntryPayload($expenseId, $assetId));
        $id = $createResponse->json('data.id');

        $response = $this->deleteJson("/api/v1/accounting/journal-entries/{$id}?tenant_id={$this->tenantId}");

        $response->assertStatus(200)
            ->assertJsonPath('success', true);

        $this->getJson("/api/v1/accounting/journal-entries/{$id}?tenant_id={$this->tenantId}")
            ->assertStatus(404);
    }

    public function test_cannot_delete_posted_journal_entry(): void
    {
        $expenseId = $this->createAccount('5000', 'Office Expense', 'expense');
        $assetId = $this->createAccount('1000', 'Cash', 'asset');
        $createResponse = $this->postJson('/api/v1/accounting/journal-entries', $this->defaultJournalEntryPayload($expenseId, $assetId));
        $id = $createResponse->json('data.id');

        $this->postJson("/api/v1/accounting/journal-entries/{$id}/post?tenant_id={$this->tenantId}")->assertStatus(200);

        $response = $this->deleteJson("/api/v1/accounting/journal-entries/{$id}?tenant_id={$this->tenantId}");

        $response->assertStatus(422)
            ->assertJsonPath('success', false);
    }

    public function test_posting_updates_account_balances(): void
    {
        $assetId = $this->createAccount('1000', 'Cash', 'asset');
        $expenseId = $this->createAccount('5000', 'Office Expense', 'expense');

        // Debit expense (increase expense balance), credit asset (decrease asset balance)
        $createResponse = $this->postJson('/api/v1/accounting/journal-entries', [
            'tenant_id' => $this->tenantId,
            'entry_date' => '2026-01-15',
            'currency' => 'LKR',
            'lines' => [
                [
                    'account_id' => $expenseId,
                    'debit_amount' => '200.0000',
                    'credit_amount' => '0.0000',
                ],
                [
                    'account_id' => $assetId,
                    'debit_amount' => '0.0000',
                    'credit_amount' => '200.0000',
                ],
            ],
        ]);
        $id = $createResponse->json('data.id');

        $this->postJson("/api/v1/accounting/journal-entries/{$id}/post?tenant_id={$this->tenantId}")->assertStatus(200);

        // Check asset account balance decreased (asset normal balance = debit; credit reduces it)
        $assetResponse = $this->getJson("/api/v1/accounts/{$assetId}?tenant_id={$this->tenantId}");
        $assetBalance = $assetResponse->json('data.current_balance');
        $this->assertEquals('-200.0000', $assetBalance);

        // Check expense account balance increased (expense normal balance = debit; debit increases it)
        $expenseResponse = $this->getJson("/api/v1/accounts/{$expenseId}?tenant_id={$this->tenantId}");
        $expenseBalance = $expenseResponse->json('data.current_balance');
        $this->assertEquals('200.0000', $expenseBalance);
    }
}
