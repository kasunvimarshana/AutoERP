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

    public function test_active_rule_cannot_be_removed_by_omission(): void
    {
        $tenantId = $this->createTenant('OMIT');
        $firstRoleId = $this->createAccountRole($tenantId, 'receivable');
        $secondRoleId = $this->createAccountRole($tenantId, 'revenue');

        $this->withTenantExecutionContext($tenantId, function () use ($tenantId, $firstRoleId, $secondRoleId): void {
            $service = app(PostingProfileService::class);
            $profile = $service->save(
                $tenantId,
                null,
                'sales_invoice',
                'Sales Invoice',
                null,
                true,
                [
                    $this->rule('receivable', $firstRoleId),
                    $this->rule('revenue', $secondRoleId),
                ],
            );

            try {
                $service->save(
                    $tenantId,
                    null,
                    'sales_invoice',
                    'Changed name must roll back',
                    null,
                    true,
                    [$this->rule('receivable', $firstRoleId)],
                    $profile,
                    (int) $profile->row_version,
                );
                self::fail('Expected omitted active rule validation to fail.');
            } catch (InvalidArgumentException $exception) {
                self::assertSame(
                    'Posting profile rules cannot be removed by omission. Submit the existing rule with is_active=false or an effective_to date.',
                    $exception->getMessage(),
                );
            }

            $profile->refresh();
            self::assertSame('Sales Invoice', $profile->name);
            self::assertCount(2, $profile->rules()->where('is_active', true)->get());
        });
    }

    public function test_existing_profile_cannot_move_to_another_tenant_scope(): void
    {
        $tenantId = $this->createTenant('OWNER');
        $otherTenantId = $this->createTenant('OTHER');
        $roleId = $this->createAccountRole($tenantId, 'receivable');

        $this->withTenantExecutionContext($tenantId, function () use ($tenantId, $otherTenantId, $roleId): void {
            $service = app(PostingProfileService::class);
            $profile = $service->save(
                $tenantId,
                null,
                'sales_invoice',
                'Sales Invoice',
                null,
                true,
                [$this->rule('receivable', $roleId)],
            );

            $this->expectException(InvalidArgumentException::class);
            $this->expectExceptionMessage('Posting profile tenant and organization scope cannot be changed.');

            $service->save(
                $otherTenantId,
                null,
                'sales_invoice',
                'Sales Invoice',
                null,
                true,
                [$this->rule('receivable', $roleId)],
                $profile,
                (int) $profile->row_version,
            );
        });
    }

    public function test_stale_profile_version_is_rejected_without_mutation(): void
    {
        $tenantId = $this->createTenant('STALE');
        $roleId = $this->createAccountRole($tenantId, 'receivable');

        $this->withTenantExecutionContext($tenantId, function () use ($tenantId, $roleId): void {
            $service = app(PostingProfileService::class);
            $profile = $service->save(
                $tenantId,
                null,
                'sales_invoice',
                'Sales Invoice',
                null,
                true,
                [$this->rule('receivable', $roleId)],
            );
            $originalVersion = (int) $profile->row_version;
            $updated = $service->save(
                $tenantId,
                null,
                'sales_invoice',
                'Sales Invoice Updated',
                null,
                true,
                [$this->rule('receivable', $roleId)],
                $profile,
                $originalVersion,
            );

            self::assertGreaterThan($originalVersion, (int) $updated->row_version);

            try {
                $service->save(
                    $tenantId,
                    null,
                    'sales_invoice',
                    'Stale overwrite',
                    null,
                    true,
                    [$this->rule('receivable', $roleId)],
                    $updated,
                    $originalVersion,
                );
                self::fail('Expected stale posting profile version to fail.');
            } catch (InvalidArgumentException $exception) {
                self::assertSame(
                    'Posting profile was changed by another request. Reload it before updating.',
                    $exception->getMessage(),
                );
            }

            self::assertSame('Sales Invoice Updated', $updated->refresh()->name);
        });
    }

    /** @return array<string, mixed> */
    private function rule(string $lineKey, int $roleId): array
    {
        return [
            'line_key' => $lineKey,
            'account_role_id' => $roleId,
            'effective_from' => '2026-01-01',
            'is_active' => true,
        ];
    }

    private function createTenant(string $label): int
    {
        $suffix = Str::upper(Str::random(5));

        return (int) DB::table('tenants')->insertGetId([
            'uuid' => (string) Str::uuid(),
            'code' => 'TEN-'.$label.'-'.$suffix,
            'name' => $label.' Tenant '.$suffix,
            'slug' => Str::lower($label).'-tenant-'.Str::lower($suffix),
            'status' => 'active',
            'status_changed_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function createAccountRole(int $tenantId, string $code): int
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
}
