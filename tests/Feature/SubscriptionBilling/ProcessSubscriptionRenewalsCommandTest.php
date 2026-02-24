<?php

namespace Tests\Feature\SubscriptionBilling;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;
use Modules\SubscriptionBilling\Infrastructure\Jobs\RenewSubscriptionJob;
use Tests\TestCase;

/**
 * Feature tests for the subscriptions:process-renewals Artisan command.
 *
 * Covers:
 *  - Active subscriptions past their period end are dispatched a renewal job.
 *  - Trial subscriptions past their period end are dispatched a renewal job.
 *  - Subscriptions not yet due are skipped.
 *  - Cancelled subscriptions are skipped.
 *  - --tenant option scopes processing to the specified tenant.
 *  - --chunk option is accepted and the command exits successfully.
 *  - An empty dataset dispatches no jobs.
 */
class ProcessSubscriptionRenewalsCommandTest extends TestCase
{
    use RefreshDatabase;

    private string $tenantId;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenantId = (string) Str::uuid();

        $this->app->instance('current.tenant.id', $this->tenantId);
    }

    // -----------------------------------------------------------------------
    // Active subscription past period end → job dispatched
    // -----------------------------------------------------------------------

    public function test_active_subscription_due_for_renewal_dispatches_job(): void
    {
        Queue::fake();

        $planId = $this->seedPlan();
        $this->seedSubscription($planId, status: 'active', periodEnd: now()->subDay());

        $this->artisan('subscriptions:process-renewals')
            ->assertSuccessful();

        Queue::assertPushed(RenewSubscriptionJob::class, 1);
    }

    // -----------------------------------------------------------------------
    // Trial subscription past period end → job dispatched
    // -----------------------------------------------------------------------

    public function test_trial_subscription_due_for_renewal_dispatches_job(): void
    {
        Queue::fake();

        $planId = $this->seedPlan();
        $this->seedSubscription($planId, status: 'trial', periodEnd: now()->subDay());

        $this->artisan('subscriptions:process-renewals')
            ->assertSuccessful();

        Queue::assertPushed(RenewSubscriptionJob::class, 1);
    }

    // -----------------------------------------------------------------------
    // Subscription not yet due → not dispatched
    // -----------------------------------------------------------------------

    public function test_subscription_not_yet_due_is_skipped(): void
    {
        Queue::fake();

        $planId = $this->seedPlan();
        $this->seedSubscription($planId, status: 'active', periodEnd: now()->addWeek());

        $this->artisan('subscriptions:process-renewals')
            ->assertSuccessful();

        Queue::assertNothingPushed();
    }

    // -----------------------------------------------------------------------
    // Cancelled subscription → not dispatched
    // -----------------------------------------------------------------------

    public function test_cancelled_subscription_is_skipped(): void
    {
        Queue::fake();

        $planId = $this->seedPlan();
        $this->seedSubscription($planId, status: 'cancelled', periodEnd: now()->subDay());

        $this->artisan('subscriptions:process-renewals')
            ->assertSuccessful();

        Queue::assertNothingPushed();
    }

    // -----------------------------------------------------------------------
    // No subscriptions → no jobs dispatched
    // -----------------------------------------------------------------------

    public function test_no_subscriptions_dispatches_no_jobs(): void
    {
        Queue::fake();

        $this->artisan('subscriptions:process-renewals')
            ->assertSuccessful();

        Queue::assertNothingPushed();
    }

    // -----------------------------------------------------------------------
    // Mixed due / not-due — only due receive jobs
    // -----------------------------------------------------------------------

    public function test_only_due_subscriptions_are_dispatched(): void
    {
        Queue::fake();

        $planId = $this->seedPlan();
        $this->seedSubscription($planId, status: 'active', periodEnd: now()->subDay());
        $this->seedSubscription($planId, status: 'active', periodEnd: now()->addDay());
        $this->seedSubscription($planId, status: 'trial',  periodEnd: now()->subHour());

        $this->artisan('subscriptions:process-renewals')
            ->assertSuccessful();

        Queue::assertPushed(RenewSubscriptionJob::class, 2);
    }

    // -----------------------------------------------------------------------
    // --tenant option scopes to the given tenant
    // -----------------------------------------------------------------------

    public function test_tenant_option_scopes_to_specified_tenant(): void
    {
        Queue::fake();

        // Subscription due for the current tenant.
        $planId = $this->seedPlan();
        $this->seedSubscription($planId, status: 'active', periodEnd: now()->subDay());

        // Subscriptions due for a different tenant.
        $otherTenantId = (string) Str::uuid();
        $otherPlanId   = $this->seedPlanForTenant($otherTenantId);
        $this->seedSubscriptionForTenant($otherTenantId, $otherPlanId, status: 'active', periodEnd: now()->subDay());
        $this->seedSubscriptionForTenant($otherTenantId, $otherPlanId, status: 'active', periodEnd: now()->subDay());

        // Run only for the current tenant.
        $this->artisan('subscriptions:process-renewals', ['--tenant' => $this->tenantId])
            ->assertSuccessful();

        Queue::assertPushed(RenewSubscriptionJob::class, 1);
    }

    // -----------------------------------------------------------------------
    // --chunk option is respected (command exits successfully)
    // -----------------------------------------------------------------------

    public function test_chunk_option_is_accepted_and_command_succeeds(): void
    {
        Queue::fake();

        $planId = $this->seedPlan();
        $this->seedSubscription($planId, status: 'active', periodEnd: now()->subDay());
        $this->seedSubscription($planId, status: 'active', periodEnd: now()->subDay());
        $this->seedSubscription($planId, status: 'active', periodEnd: now()->subDay());

        $this->artisan('subscriptions:process-renewals', ['--chunk' => 2])
            ->assertSuccessful();

        // All 3 subscriptions must be dispatched regardless of chunk size.
        Queue::assertPushed(RenewSubscriptionJob::class, 3);
    }

    // -----------------------------------------------------------------------
    // Helpers
    // -----------------------------------------------------------------------

    private function seedPlan(): string
    {
        return $this->seedPlanForTenant($this->tenantId);
    }

    private function seedPlanForTenant(string $tenantId): string
    {
        $id = (string) Str::uuid();

        \Illuminate\Support\Facades\DB::table('subscription_plans')->insert([
            'id'            => $id,
            'tenant_id'     => $tenantId,
            'name'          => 'Test Plan',
            'code'          => (string) Str::uuid(),
            'billing_cycle' => 'monthly',
            'price'         => '9.99000000',
            'trial_days'    => 0,
            'is_active'     => 1,
            'created_at'    => now(),
            'updated_at'    => now(),
        ]);

        return $id;
    }

    private function seedSubscription(string $planId, string $status, \Carbon\Carbon $periodEnd): string
    {
        return $this->seedSubscriptionForTenant($this->tenantId, $planId, $status, $periodEnd);
    }

    private function seedSubscriptionForTenant(
        string $tenantId,
        string $planId,
        string $status,
        \Carbon\Carbon $periodEnd,
    ): string {
        $id = (string) Str::uuid();

        \Illuminate\Support\Facades\DB::table('subscriptions')->insert([
            'id'                   => $id,
            'tenant_id'            => $tenantId,
            'plan_id'              => $planId,
            'subscriber_type'      => 'customer',
            'subscriber_id'        => (string) Str::uuid(),
            'status'               => $status,
            'amount'               => '9.99000000',
            'current_period_start' => now()->subMonth()->toDateTimeString(),
            'current_period_end'   => $periodEnd->toDateTimeString(),
            'created_at'           => now(),
            'updated_at'           => now(),
        ]);

        return $id;
    }
}
