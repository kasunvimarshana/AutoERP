<?php

declare(strict_types=1);

namespace Tests\Feature\Uom;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Modules\Core\Contracts\PasswordHasherInterface;
use Tests\TestCase;

final class UomApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_uom_create(): void
    {
        $context = $this->createAuthContext();

        $this->withAuth($context)->postJson('/api/v1/uoms', $this->uomPayload())
            ->assertCreated()
            ->assertJsonPath('data.code', 'PCS')
            ->assertJsonPath('data.type', 'unit')
            ->assertJsonPath('data.category', 'quantity');
    }

    public function test_duplicate_code_prevention(): void
    {
        $context = $this->createAuthContext();
        $this->withAuth($context)->postJson('/api/v1/uoms', $this->uomPayload())->assertCreated();

        $this->withAuth($context)->postJson('/api/v1/uoms', $this->uomPayload(['name' => 'Pieces duplicate']))
            ->assertUnprocessable()
            ->assertJsonPath('success', false);
    }

    public function test_uom_update(): void
    {
        $context = $this->createAuthContext();
        $id = (int) $this->withAuth($context)->postJson('/api/v1/uoms', $this->uomPayload())->json('data.id');

        $this->withAuth($context)->putJson('/api/v1/uoms/'.$id, ['name' => 'Pieces Updated'])
            ->assertOk()
            ->assertJsonPath('data.name', 'Pieces Updated');
    }

    public function test_uom_deactivate(): void
    {
        $context = $this->createAuthContext();
        $id = (int) $this->withAuth($context)->postJson('/api/v1/uoms', $this->uomPayload())->json('data.id');

        $this->withAuth($context)->patchJson('/api/v1/uoms/'.$id.'/deactivate')
            ->assertOk()
            ->assertJsonPath('data.is_active', false);
    }

    public function test_uom_lookup(): void
    {
        $context = $this->createAuthContext();
        $this->withAuth($context)->postJson('/api/v1/uoms', $this->uomPayload())->assertCreated();

        $this->withAuth($context)->getJson('/api/v1/uoms/lookup?search=PC')
            ->assertOk()
            ->assertJsonPath('data.0.code', 'PCS');
    }

    public function test_conversion_create_returns_readable_relations(): void
    {
        $context = $this->createAuthContext();
        [$boxId, $pcsId] = $this->createBoxAndPieces($context);

        $this->withAuth($context)->postJson('/api/v1/uom-conversions', [
            'from_uom_id' => $boxId,
            'to_uom_id' => $pcsId,
            'conversion_factor' => '12',
            'is_active' => true,
        ])
            ->assertCreated()
            ->assertJsonPath('data.from_uom.code', 'BOX')
            ->assertJsonPath('data.to_uom.code', 'PCS')
            ->assertJsonPath('data.conversion_factor', '12.000000')
            ->assertJsonMissingPath('data.from_uom_id')
            ->assertJsonMissingPath('data.to_uom_id');
    }

    public function test_duplicate_conversion_prevention(): void
    {
        $context = $this->createAuthContext();
        [$boxId, $pcsId] = $this->createBoxAndPieces($context);

        $payload = [
            'from_uom_id' => $boxId,
            'to_uom_id' => $pcsId,
            'conversion_factor' => '12',
        ];

        $this->withAuth($context)->postJson('/api/v1/uom-conversions', $payload)->assertCreated();

        $this->withAuth($context)->postJson('/api/v1/uom-conversions', $payload)
            ->assertUnprocessable()
            ->assertJsonPath('success', false);
    }

    public function test_conversion_factor_validation(): void
    {
        $context = $this->createAuthContext();
        [$boxId, $pcsId] = $this->createBoxAndPieces($context);

        $this->withAuth($context)->postJson('/api/v1/uom-conversions', [
            'from_uom_id' => $boxId,
            'to_uom_id' => $pcsId,
            'conversion_factor' => '0',
        ])
            ->assertUnprocessable()
            ->assertJsonPath('success', false);
    }

    public function test_prevent_same_from_and_to_uom(): void
    {
        $context = $this->createAuthContext();
        [$boxId] = $this->createBoxAndPieces($context);

        $this->withAuth($context)->postJson('/api/v1/uom-conversions', [
            'from_uom_id' => $boxId,
            'to_uom_id' => $boxId,
            'conversion_factor' => '1',
        ])
            ->assertUnprocessable()
            ->assertJsonPath('success', false);
    }

    public function test_quantity_conversion(): void
    {
        $context = $this->createAuthContext();
        [$boxId, $pcsId] = $this->createBoxAndPieces($context);
        $this->withAuth($context)->postJson('/api/v1/uom-conversions', [
            'from_uom_id' => $boxId,
            'to_uom_id' => $pcsId,
            'conversion_factor' => '12',
        ])->assertCreated();

        $this->withAuth($context)->postJson('/api/v1/uom-conversions/convert', [
            'from_uom_id' => $boxId,
            'to_uom_id' => $pcsId,
            'quantity' => '5',
        ])
            ->assertOk()
            ->assertJsonPath('quantity', '5.000000')
            ->assertJsonPath('from_uom.code', 'BOX')
            ->assertJsonPath('to_uom.code', 'PCS')
            ->assertJsonPath('conversion_factor', '12.000000')
            ->assertJsonPath('converted_quantity', '60.000000');
    }

    public function test_decimal_safe_fractional_conversion(): void
    {
        $context = $this->createAuthContext();
        [$boxId, $pcsId] = $this->createBoxAndPieces($context);
        $this->withAuth($context)->postJson('/api/v1/uom-conversions', [
            'from_uom_id' => $boxId,
            'to_uom_id' => $pcsId,
            'conversion_factor' => '2.5',
        ])->assertCreated();

        $this->withAuth($context)->postJson('/api/v1/uom-conversions/convert', [
            'from_uom_id' => $boxId,
            'to_uom_id' => $pcsId,
            'quantity' => '1.5',
        ])
            ->assertOk()
            ->assertJsonPath('quantity', '1.500000')
            ->assertJsonPath('conversion_factor', '2.500000')
            ->assertJsonPath('converted_quantity', '3.750000');
    }

    public function test_inactive_uom_cannot_be_used_in_conversion(): void
    {
        $context = $this->createAuthContext();
        [$boxId, $pcsId] = $this->createBoxAndPieces($context);
        $this->withAuth($context)->patchJson('/api/v1/uoms/'.$pcsId.'/deactivate')->assertOk();

        $this->withAuth($context)->postJson('/api/v1/uom-conversions', [
            'from_uom_id' => $boxId,
            'to_uom_id' => $pcsId,
            'conversion_factor' => '12',
        ])
            ->assertUnprocessable()
            ->assertJsonPath('success', false);
    }

    public function test_tenant_isolation(): void
    {
        $tenantA = $this->createAuthContext(['code' => 'A', 'email' => 'a@example.test']);
        $tenantB = $this->createAuthContext(['code' => 'B', 'email' => 'b@example.test']);
        $id = (int) $this->withAuth($tenantA)->postJson('/api/v1/uoms', $this->uomPayload())->json('data.id');

        $this->withAuth($tenantB)->getJson('/api/v1/uoms/'.$id)
            ->assertForbidden();
    }

    public function test_organization_isolation(): void
    {
        $context = $this->createAuthContext();
        $otherTenant = $this->createAuthContext(['code' => 'OTHER', 'email' => 'other@example.test']);

        $this->withAuth($context)->postJson('/api/v1/uoms', $this->uomPayload([
            'organization_unit_id' => $otherTenant['organization_unit_id'],
        ]))
            ->assertUnprocessable()
            ->assertJsonPath('success', false);
    }

    public function test_api_validation_error_format(): void
    {
        $context = $this->createAuthContext();

        $this->withAuth($context)->postJson('/api/v1/uoms', [
            'decimal_precision' => -1,
        ])
            ->assertUnprocessable()
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'Validation failed.')
            ->assertJsonStructure(['errors' => ['code', 'name', 'symbol']]);
    }

    private function uomPayload(array $overrides = []): array
    {
        return [
            'code' => 'PCS',
            'name' => 'Pieces',
            'symbol' => 'pcs',
            'type' => 'unit',
            'category' => 'quantity',
            'decimal_precision' => 0,
            'allow_fractional_quantity' => false,
            'is_base' => true,
            'is_active' => true,
            ...$overrides,
        ];
    }

    private function createBoxAndPieces(array $context): array
    {
        $pcsId = (int) $this->withAuth($context)->postJson('/api/v1/uoms', $this->uomPayload())->json('data.id');
        $boxId = (int) $this->withAuth($context)->postJson('/api/v1/uoms', $this->uomPayload([
            'code' => 'BOX',
            'name' => 'Box',
            'symbol' => 'box',
            'is_base' => false,
        ]))->json('data.id');

        return [$boxId, $pcsId];
    }

    private function withAuth(array $context): self
    {
        return $this->withToken($context['token'])->withHeaders([
            'X-Tenant-Id' => (string) $context['tenant_id'],
            'X-Organization-Unit-Id' => (string) $context['organization_unit_id'],
        ]);
    }

    private function createAuthContext(array $overrides = []): array
    {
        $now = now();
        $code = strtoupper((string) ($overrides['code'] ?? 'AUTOERP'));
        $tenantId = (int) DB::table('tenants')->insertGetId([
            'uuid' => (string) Str::uuid(),
            'code' => $code,
            'name' => $code.' Tenant',
            'slug' => strtolower($code).'-tenant',
            'status' => 'active',
            'is_active' => true,
            'is_isolated' => true,
            'row_version' => 1,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        $organizationUnitId = (int) DB::table('organization_units')->insertGetId([
            'tenant_id' => $tenantId,
            'name' => 'Main',
            'code' => 'MAIN',
            'path' => '/main',
            'is_active' => true,
            'row_version' => 1,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        $email = (string) ($overrides['email'] ?? 'admin@example.test');
        $userId = (int) DB::table('users')->insertGetId([
            'tenant_id' => $tenantId,
            'organization_unit_id' => $organizationUnitId,
            'first_name' => 'Unit',
            'last_name' => 'Tester',
            'email' => $email,
            'password' => app(PasswordHasherInterface::class)->hash('secret-password'),
            'status' => 'active',
            'row_version' => 1,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        DB::table('user_tenants')->insert([
            'tenant_id' => $tenantId,
            'organization_unit_id' => $organizationUnitId,
            'user_id' => $userId,
            'is_default' => true,
            'row_version' => 1,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        DB::table('auth_providers')->insert([
            'tenant_id' => $tenantId,
            'organization_unit_id' => $organizationUnitId,
            'provider_key' => 'internal',
            'name' => 'Internal password login',
            'guard_name' => 'auth-api',
            'provider_name' => 'users',
            'driver' => 'internal',
            'status' => 'active',
            'is_sso' => false,
            'row_version' => 1,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $token = (string) $this->postJson('/api/v1/auth/login', [
            'tenant_id' => $tenantId,
            'organization_unit_id' => $organizationUnitId,
            'login_identifier' => $email,
            'password' => 'secret-password',
        ])->assertOk()->json('token');

        return [
            'tenant_id' => $tenantId,
            'organization_unit_id' => $organizationUnitId,
            'user_id' => $userId,
            'token' => $token,
        ];
    }
}
