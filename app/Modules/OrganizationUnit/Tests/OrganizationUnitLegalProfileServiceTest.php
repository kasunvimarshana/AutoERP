<?php

declare(strict_types=1);

namespace Modules\OrganizationUnit\Tests;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Mockery\MockInterface;
use Modules\Audit\Contracts\AuditRecorderInterface;
use Modules\OrganizationUnit\Contracts\OrganizationUnitLegalProfileReaderInterface;
use Modules\OrganizationUnit\Services\LegalProfile\OrganizationUnitLegalProfileService;
use Tests\Support\OrganizationUnitFixture;
use Tests\TestCase;

final class OrganizationUnitLegalProfileServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_profile_create_update_read_and_stale_version_guard(): void
    {
        [$tenantId, $organizationUnitId] = $this->scope();
        $this->mock(AuditRecorderInterface::class, function (MockInterface $mock): void {
            $mock->shouldReceive('record')->twice();
        });

        $created = $this->withTenantExecutionContext(
            $tenantId,
            fn () => app(OrganizationUnitLegalProfileService::class)->upsert(
                $tenantId,
                $organizationUnitId,
                $this->payload('Original Legal Name'),
            ),
        );
        self::assertSame(1, (int) $created->row_version);

        $snapshot = $this->withTenantExecutionContext(
            $tenantId,
            fn () => app(OrganizationUnitLegalProfileReaderInterface::class)->find($tenantId, $organizationUnitId),
        );
        self::assertNotNull($snapshot);
        self::assertSame('Original Legal Name', $snapshot->legalName);
        self::assertSame('1 Registered Road, Colombo, 00100, Sri Lanka', $snapshot->address);

        $updated = $this->withTenantExecutionContext(
            $tenantId,
            fn () => app(OrganizationUnitLegalProfileService::class)->upsert(
                $tenantId,
                $organizationUnitId,
                [
                    ...$this->payload('Updated Legal Name'),
                    'expected_version' => 1,
                ],
            ),
        );
        self::assertSame(2, (int) $updated->row_version);
        self::assertSame('Updated Legal Name', $updated->legal_name);

        try {
            $this->withTenantExecutionContext(
                $tenantId,
                fn () => app(OrganizationUnitLegalProfileService::class)->upsert(
                    $tenantId,
                    $organizationUnitId,
                    [
                        ...$this->payload('Stale Legal Name'),
                        'expected_version' => 1,
                    ],
                ),
            );
            self::fail('A stale legal profile update should fail.');
        } catch (ValidationException $exception) {
            self::assertArrayHasKey('expected_version', $exception->errors());
        }

        self::assertSame(
            'Updated Legal Name',
            DB::table('organization_unit_legal_profiles')
                ->where('tenant_id', $tenantId)
                ->where('organization_unit_id', $organizationUnitId)
                ->value('legal_name'),
        );
    }

    /** @return array<string, mixed> */
    private function payload(string $legalName): array
    {
        return [
            'legal_name' => $legalName,
            'tin' => 'TIN-001',
            'vat_registration_number' => 'VAT-001',
            'svat_registration_number' => 'SVAT-001',
            'address_line_1' => '1 Registered Road',
            'address_line_2' => null,
            'city' => 'Colombo',
            'state' => null,
            'postal_code' => '00100',
            'country' => 'Sri Lanka',
            'phone' => '0112000000',
            'email' => 'legal@example.test',
        ];
    }

    /** @return array{int, int} */
    private function scope(): array
    {
        $suffix = Str::upper(Str::random(6));
        $tenantId = (int) DB::table('tenants')->insertGetId([
            'uuid' => (string) Str::uuid(),
            'code' => 'TEN-'.$suffix,
            'name' => 'Tenant '.$suffix,
            'slug' => 'tenant-'.Str::lower($suffix),
            'status' => 'active',
            'status_changed_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $organizationUnitId = OrganizationUnitFixture::create([
            'tenant_id' => $tenantId,
            'name' => 'Legal Profile Unit',
            'code' => 'LEGAL-'.$suffix,
            'create_legal_profile' => false,
        ]);

        return [$tenantId, $organizationUnitId];
    }
}
