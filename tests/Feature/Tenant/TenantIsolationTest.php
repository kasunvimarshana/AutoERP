<?php

declare(strict_types=1);

namespace Tests\Feature\Tenant;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use LogicException;
use Modules\Core\Contracts\TenantExecutionContextInterface;
use Modules\Tenant\Constants\TenantOnboardingStep;
use Modules\Tenant\Constants\TenantOnboardingStepStatus;
use Modules\Tenant\Models\TenantOnboardingStepModel;
use Tests\TestCase;

final class TenantIsolationTest extends TestCase
{
    use RefreshDatabase;

    public function test_tenant_owned_reads_fail_closed_and_never_cross_tenant_boundaries(): void
    {
        $tenantA = $this->tenant('ISOLATION-A');
        $tenantB = $this->tenant('ISOLATION-B');
        $execution = app(TenantExecutionContextInterface::class);

        $execution->runForTenant($tenantA, fn (): TenantOnboardingStepModel => $this->step(
            $tenantA,
            TenantOnboardingStep::ROOT_ORGANIZATION,
        ));
        $execution->runForTenant($tenantB, fn (): TenantOnboardingStepModel => $this->step(
            $tenantB,
            TenantOnboardingStep::PERMISSION_CATALOGUE,
        ));

        self::assertSame(0, TenantOnboardingStepModel::query()->count());
        self::assertSame(
            [TenantOnboardingStep::ROOT_ORGANIZATION],
            $execution->runForTenant(
                $tenantA,
                static fn (): array => TenantOnboardingStepModel::query()
                    ->pluck('step')
                    ->map(static fn (mixed $step): string => (string) $step)
                    ->all(),
            ),
        );
        self::assertSame(
            [TenantOnboardingStep::PERMISSION_CATALOGUE],
            $execution->runForTenant(
                $tenantB,
                static fn (): array => TenantOnboardingStepModel::query()
                    ->pluck('step')
                    ->map(static fn (mixed $step): string => (string) $step)
                    ->all(),
            ),
        );
        self::assertSame(
            2,
            $execution->runAsControlPlane(
                static fn (): int => TenantOnboardingStepModel::query()->count(),
            ),
        );
    }

    public function test_tenant_owned_writes_reject_mismatched_execution_context(): void
    {
        $tenantA = $this->tenant('WRITE-A');
        $tenantB = $this->tenant('WRITE-B');
        $execution = app(TenantExecutionContextInterface::class);

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Tenant context mismatch');

        $execution->runForTenant($tenantA, fn (): TenantOnboardingStepModel => $this->step(
            $tenantB,
            TenantOnboardingStep::ROOT_ORGANIZATION,
        ));
    }

    private function step(int $tenantId, string $step): TenantOnboardingStepModel
    {
        return TenantOnboardingStepModel::query()->create([
            'tenant_id' => $tenantId,
            'step' => $step,
            'owner_module' => TenantOnboardingStep::owner($step),
            'status' => TenantOnboardingStepStatus::PENDING,
            'attempt_count' => 0,
        ]);
    }

    private function tenant(string $code): int
    {
        return (int) DB::table('tenants')->insertGetId([
            'uuid' => (string) Str::uuid(),
            'code' => $code,
            'name' => "Tenant {$code}",
            'slug' => strtolower($code),
            'status' => 'draft',
            'status_changed_at' => now(),
            'row_version' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
