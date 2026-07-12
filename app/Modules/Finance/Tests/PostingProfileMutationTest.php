<?php

declare(strict_types=1);

namespace Modules\Finance\Tests;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;
use Modules\Finance\Models\FinancePostingProfile;
use Modules\Finance\Services\PostingProfileService;
use Tests\TestCase;

final class PostingProfileMutationTest extends TestCase
{
    use RefreshDatabase;

    public function test_profile_update_deactivates_omitted_rules_without_deleting_history(): void
    {
        $tenantId = $this->createTenant('PROFILE-REPLACE');
        [$receivableRoleId, $revenueRoleId] = $this->createRoles($tenantId);

        $profile = $this->withTenantExecutionContext($tenantId, fn (): FinancePostingProfile => app(PostingProfileService::class)->save(
            $tenantId,
            null,
            'sales_invoice',
            'Sales Invoice',
            null,
            true,
            [
                $this->rule('receivable', $receivableRoleId),
                $this->rule('revenue', $revenueRoleId),
            ],
        ));

        $updated = $this->withTenantExecutionContext($tenantId, fn (): FinancePostingProfile => app(PostingProfileService::class)->save(
            $tenantId,
            null,
            'sales_invoice',
            'Sales Invoice Updated',
            null,
            true,
            [$this->rule('receivable', $receivableRoleId)],
            $profile,
        ));

        self::assertSame('Sales Invoice Updated', (string) $updated->name);
        $this->assertDatabaseHas('finance_posting_profile_rules', [
            'posting_profile_id' => $profile->getKey(),
            'line_key' => 'receivable',
            'is_active' => true,
        ]);
        $this->assertDatabaseHas('finance_posting_profile_rules', [
            'posting_profile_id' => $profile->getKey(),
            'line_key' => 'revenue',
            'is_active' => false,
        ]);
        $this->assertDatabaseCount('finance_posting_profile_rules', 2);
    }

    public function test_existing_profile_cannot_be_moved_to_another_tenant(): void
    {
        $tenantId = $this->createTenant('PROFILE-OWNER');
        $otherTenantId = $this->createTenant('PROFILE-OTHER');
        [$receivableRoleId] = $this->createRoles($tenantId);

        $profile = $this->withTenantExecutionContext($tenantId, fn (): FinancePostingProfile => app(PostingProfileService::class)->save(
            $tenantId,
            null,
            'sales_invoice',
            'Sales Invoice',
            null,
            true,
            [$this->rule('receivable', $receivableRoleId)],
        ));

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Posting profile tenant and organization ownership cannot be changed.');

        $this->withTenantExecutionContext($otherTenantId, fn (): FinancePostingProfile => app(PostingProfileService::class)->save(
            $otherTenantId,
            null,
            'sales_invoice',
            'Moved Sales Invoice',
            null,
            true,
            [$this->rule('receivable', $receivableRoleId)],
            $profile,
        ));
    }

    /** @return array{line_key: string, account_role_id: int, effective_from: string, is_active: bool} */
    private function rule(string $lineKey, int $roleId): array
    {
        return [
            'line_key' => $lineKey,
            'account_role_id' => $roleId,
            'effective_from' => '2026-01-01',
            'is_active' => true,
        ];
    }

    /** @return list<int> */
    private function createRoles(int $tenantId): array
    {
        return [
            $this->createRole($tenantId, 'receivable'),
            $this->createRole($tenantId, 'revenue'),
        ];
    }

    private function createRole(int $tenantId, string $code): int
    {
        return (int) DB::table('finance_account_roles')->insertGetId([
            'tenant_id' => $tenantId,
            'code' => $code,
            'name' => Str::headline($code),
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function createTenant(string $prefix): int
    {
        $suffix = Str::upper(Str::random(5));

        return (int) DB::table('tenants')->insertGetId([
            'uuid' => (string) Str::uuid(),
            'code' => $prefix.'-'.$suffix,
            'name' => Str::headline($prefix).' '.$suffix,
            'slug' => Str::lower($prefix).'-'.Str::lower($suffix),
            'status' => 'active',
            'status_changed_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
