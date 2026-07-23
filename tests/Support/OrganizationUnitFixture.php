<?php

declare(strict_types=1);

namespace Tests\Support;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Modules\OrganizationUnit\Constants\OrganizationUnitHierarchy;
use Modules\OrganizationUnit\Support\OrganizationUnitNameKey;
use RuntimeException;

final class OrganizationUnitFixture
{
    /**
     * Creates a schema-valid organization unit for integration tests.
     *
     * The first unit in a tenant becomes its protected root. Later units without
     * an explicit parent are created below that root so tests cannot manufacture
     * invalid parallel roots.
     *
     * A deterministic legal profile is created by default because printable
     * business documents fail closed when the organization identity is absent.
     * Tests that explicitly exercise a missing profile may pass
     * `create_legal_profile => false`.
     *
     * @param array<string, mixed> $attributes
     */
    public static function create(array $attributes): int
    {
        $tenantId = self::positiveInt($attributes['tenant_id'] ?? null, 'tenant_id');
        $name = trim((string) ($attributes['name'] ?? ''));
        $code = Str::upper(trim((string) ($attributes['code'] ?? '')));
        if ($name === '' || preg_match('/^[A-Z0-9][A-Z0-9_-]{0,99}$/D', $code) !== 1) {
            throw new RuntimeException('Organization-unit test fixture requires a valid name and canonical code.');
        }

        $existingRoot = DB::table('organization_units')
            ->where('tenant_id', $tenantId)
            ->where('root_marker', OrganizationUnitHierarchy::ROOT_MARKER)
            ->first(['id', 'path', 'depth']);

        $requestedParentId = self::nullablePositiveInt($attributes['parent_id'] ?? null);
        $parent = $requestedParentId !== null
            ? DB::table('organization_units')
                ->where('tenant_id', $tenantId)
                ->where('id', $requestedParentId)
                ->first(['id', 'path', 'depth'])
            : $existingRoot;

        if ($requestedParentId !== null && $parent === null) {
            throw new RuntimeException('Organization-unit test fixture parent does not belong to the tenant.');
        }

        $isRoot = $existingRoot === null && $parent === null;
        $depth = $isRoot ? 0 : ((int) $parent->depth + 1);
        $typeId = self::typeId($tenantId, $depth);
        $segment = Str::slug($code);
        if ($segment === '') {
            throw new RuntimeException('Organization-unit test fixture path segment could not be generated.');
        }
        $path = $isRoot ? '/'.$segment : rtrim((string) $parent->path, '/').'/'.$segment;
        $retiredAt = $isRoot ? null : ($attributes['retired_at'] ?? null);
        $now = now();

        $organizationUnitId = (int) DB::table('organization_units')->insertGetId([
            'tenant_id' => $tenantId,
            'type_id' => $typeId,
            'parent_id' => $isRoot ? null : (int) $parent->id,
            'name' => $name,
            'code' => $code,
            'path' => $path,
            'path_hash' => hash('sha256', $path),
            'depth' => $depth,
            'root_marker' => $isRoot ? OrganizationUnitHierarchy::ROOT_MARKER : null,
            'is_active' => $isRoot ? true : ($retiredAt === null && (bool) ($attributes['is_active'] ?? true)),
            'retired_at' => $retiredAt,
            'description' => $attributes['description'] ?? null,
            'logo_object_key' => $attributes['logo_object_key'] ?? null,
            'logo_mime_type' => $attributes['logo_mime_type'] ?? null,
            'logo_size_bytes' => $attributes['logo_size_bytes'] ?? null,
            'row_version' => max(1, (int) ($attributes['row_version'] ?? 1)),
            'created_at' => $attributes['created_at'] ?? $now,
            'updated_at' => $attributes['updated_at'] ?? $now,
        ]);

        if (($attributes['create_legal_profile'] ?? true) === true) {
            DB::table('organization_unit_legal_profiles')->insert([
                'tenant_id' => $tenantId,
                'organization_unit_id' => $organizationUnitId,
                'legal_name' => $attributes['legal_name'] ?? $name.' Legal',
                'tin' => $attributes['tin'] ?? 'TIN-'.$code,
                'vat_registration_number' => $attributes['vat_registration_number'] ?? null,
                'svat_registration_number' => $attributes['svat_registration_number'] ?? null,
                'address_line_1' => $attributes['address_line_1'] ?? '1 Test Registered Address',
                'address_line_2' => $attributes['address_line_2'] ?? null,
                'city' => $attributes['city'] ?? 'Test City',
                'state' => $attributes['state'] ?? null,
                'postal_code' => $attributes['postal_code'] ?? null,
                'country' => $attributes['country'] ?? 'Test Country',
                'phone' => $attributes['legal_phone'] ?? '0110000000',
                'email' => $attributes['legal_email'] ?? null,
                'row_version' => 1,
                'created_at' => $attributes['created_at'] ?? $now,
                'updated_at' => $attributes['updated_at'] ?? $now,
            ]);
        }

        return $organizationUnitId;
    }

    private static function typeId(int $tenantId, int $depth): int
    {
        $name = 'Test hierarchy level '.$depth;
        $nameKey = OrganizationUnitNameKey::from($name);
        $existing = DB::table('organization_unit_types')
            ->where('tenant_id', $tenantId)
            ->where('name_key', $nameKey)
            ->value('id');
        if (is_numeric($existing)) {
            return (int) $existing;
        }

        return (int) DB::table('organization_unit_types')->insertGetId([
            'tenant_id' => $tenantId,
            'name' => $name,
            'name_key' => $nameKey,
            'level' => $depth,
            'is_active' => true,
            'row_version' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private static function positiveInt(mixed $value, string $field): int
    {
        $resolved = self::nullablePositiveInt($value);
        if ($resolved === null) {
            throw new RuntimeException("Organization-unit test fixture requires {$field}.");
        }

        return $resolved;
    }

    private static function nullablePositiveInt(mixed $value): ?int
    {
        return is_numeric($value) && (int) $value > 0 ? (int) $value : null;
    }

    private function __construct() {}
}
