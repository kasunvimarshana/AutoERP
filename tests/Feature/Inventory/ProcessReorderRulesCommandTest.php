<?php

namespace Tests\Feature\Inventory;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;
use Modules\Inventory\Infrastructure\Jobs\CheckReorderRuleJob;
use Tests\TestCase;

/**
 * Feature tests for the inventory:process-reorder-rules Artisan command.
 *
 * Covers:
 *  - Only active reorder rules trigger job dispatch.
 *  - Inactive rules are silently skipped.
 *  - --tenant option scopes processing to the specified tenant.
 *  - --chunk option controls batch size (command exits successfully).
 */
class ProcessReorderRulesCommandTest extends TestCase
{
    use RefreshDatabase;

    private string $tenantId;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenantId = (string) Str::uuid();

        // Bind a current tenant so HasTenantScope and creating hooks work.
        $this->app->instance('current.tenant.id', $this->tenantId);
    }

    // -----------------------------------------------------------------------
    // Active rules → jobs dispatched
    // -----------------------------------------------------------------------

    public function test_active_reorder_rules_dispatch_jobs(): void
    {
        Queue::fake();

        $this->seedReorderRule(isActive: true);
        $this->seedReorderRule(isActive: true);

        $this->artisan('inventory:process-reorder-rules')
            ->assertSuccessful();

        Queue::assertPushed(CheckReorderRuleJob::class, 2);
    }

    // -----------------------------------------------------------------------
    // Inactive rules → no jobs dispatched
    // -----------------------------------------------------------------------

    public function test_inactive_reorder_rules_are_skipped(): void
    {
        Queue::fake();

        $this->seedReorderRule(isActive: false);
        $this->seedReorderRule(isActive: false);

        $this->artisan('inventory:process-reorder-rules')
            ->assertSuccessful();

        Queue::assertNothingPushed();
    }

    // -----------------------------------------------------------------------
    // Mixed active / inactive
    // -----------------------------------------------------------------------

    public function test_only_active_rules_are_dispatched_when_mixed(): void
    {
        Queue::fake();

        $this->seedReorderRule(isActive: true);
        $this->seedReorderRule(isActive: false);
        $this->seedReorderRule(isActive: true);

        $this->artisan('inventory:process-reorder-rules')
            ->assertSuccessful();

        Queue::assertPushed(CheckReorderRuleJob::class, 2);
    }

    // -----------------------------------------------------------------------
    // --tenant option scopes to the given tenant
    // -----------------------------------------------------------------------

    public function test_tenant_option_scopes_to_specified_tenant(): void
    {
        Queue::fake();

        // Rules for the current tenant.
        $this->seedReorderRule(isActive: true);

        // Rules for a different tenant — use withoutScopes so the global
        // tenant scope does not filter these out during insertion.
        $otherTenantId = (string) Str::uuid();
        $this->seedReorderRuleForTenant($otherTenantId, isActive: true);
        $this->seedReorderRuleForTenant($otherTenantId, isActive: true);

        // Run only for the current tenant.
        $this->artisan('inventory:process-reorder-rules', ['--tenant' => $this->tenantId])
            ->assertSuccessful();

        Queue::assertPushed(CheckReorderRuleJob::class, 1);
    }

    // -----------------------------------------------------------------------
    // --chunk option is respected (command exits successfully)
    // -----------------------------------------------------------------------

    public function test_chunk_option_is_accepted_and_command_succeeds(): void
    {
        Queue::fake();

        $this->seedReorderRule(isActive: true);
        $this->seedReorderRule(isActive: true);
        $this->seedReorderRule(isActive: true);

        $this->artisan('inventory:process-reorder-rules', ['--chunk' => 2])
            ->assertSuccessful();

        // All 3 jobs must be dispatched regardless of chunk size.
        Queue::assertPushed(CheckReorderRuleJob::class, 3);
    }

    // -----------------------------------------------------------------------
    // Helpers
    // -----------------------------------------------------------------------

    /**
     * Insert a reorder rule row for the current tenant via DB::table()
     * to bypass Eloquent global scopes during seeding.
     */
    private function seedReorderRule(bool $isActive): void
    {
        $this->seedReorderRuleForTenant($this->tenantId, $isActive);
    }

    private function seedReorderRuleForTenant(string $tenantId, bool $isActive): void
    {
        \Illuminate\Support\Facades\DB::table('inventory_reorder_rules')->insert([
            'id'            => (string) Str::uuid(),
            'tenant_id'     => $tenantId,
            'product_id'    => (string) Str::uuid(),
            'location_id'   => null,
            'reorder_point' => '10.00000000',
            'min_qty'       => '5.00000000',
            'max_qty'       => '100.00000000',
            'lead_time_days' => 1,
            'is_active'     => $isActive ? 1 : 0,
            'created_at'    => now(),
            'updated_at'    => now(),
        ]);
    }
}
