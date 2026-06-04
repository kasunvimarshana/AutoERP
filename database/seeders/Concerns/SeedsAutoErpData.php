<?php

declare(strict_types=1);

namespace Database\Seeders\Concerns;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

trait SeedsAutoErpData
{
    protected function defaultTenantId(): ?int
    {
        if (! Schema::hasTable('tenants')) {
            return null;
        }

        $code = strtoupper(trim((string) env('AUTH_LOCAL_TENANT_CODE', 'AUTOERP')));
        $id = DB::table('tenants')->where('code', $code)->value('id')
            ?? DB::table('tenants')->orderBy('id')->value('id');

        return $id === null ? null : (int) $id;
    }

    protected function defaultOrganizationUnitId(int $tenantId): ?int
    {
        if (! Schema::hasTable('organization_units')) {
            return null;
        }

        $code = strtoupper(trim((string) env('AUTH_LOCAL_ORGANIZATION_UNIT_CODE', 'MAIN')));
        $id = DB::table('organization_units')
            ->where('tenant_id', $tenantId)
            ->where('code', $code)
            ->value('id')
            ?? DB::table('organization_units')
                ->where('tenant_id', $tenantId)
                ->orderByDesc('is_active')
                ->orderBy('id')
                ->value('id');

        return $id === null ? null : (int) $id;
    }

    protected function defaultUserId(int $tenantId): ?int
    {
        if (! Schema::hasTable('users')) {
            return null;
        }

        $email = strtolower(trim((string) env('AUTH_LOCAL_ADMIN_EMAIL', 'admin@example.com')));
        $id = DB::table('users')
            ->where('tenant_id', $tenantId)
            ->where('email', $email)
            ->value('id')
            ?? DB::table('users')->where('tenant_id', $tenantId)->orderBy('id')->value('id');

        return $id === null ? null : (int) $id;
    }

    protected function currencyCode(): string
    {
        return strtoupper(trim((string) env('AUTH_LOCAL_TENANT_CURRENCY', 'USD'))) ?: 'USD';
    }

    protected function currencyId(?string $code = null): ?int
    {
        if (! Schema::hasTable('currencies')) {
            return null;
        }

        $currencyCode = strtoupper(trim((string) ($code ?? $this->currencyCode())));
        $id = DB::table('currencies')->where('code', $currencyCode)->value('id')
            ?? DB::table('currencies')->orderBy('id')->value('id');

        return $id === null ? null : (int) $id;
    }

    protected function accountId(int $tenantId, string $code): ?int
    {
        return $this->idBy('accounts', ['tenant_id' => $tenantId, 'code' => $code]);
    }

    protected function paymentTermId(int $tenantId, string $name = 'Net 30'): ?int
    {
        return $this->idBy('payment_terms', ['tenant_id' => $tenantId, 'name' => $name])
            ?? $this->idBy('payment_terms', ['tenant_id' => $tenantId, 'name' => 'Due on Receipt']);
    }

    protected function taxGroupId(int $tenantId, string $name = 'Zero Rated'): ?int
    {
        return $this->idBy('tax_groups', ['tenant_id' => $tenantId, 'name' => $name]);
    }

    /**
     * @param  array<string, mixed>  $criteria
     */
    protected function idBy(string $table, array $criteria): ?int
    {
        if (! Schema::hasTable($table)) {
            return null;
        }

        $query = DB::table($table);
        foreach ($criteria as $column => $value) {
            if (! Schema::hasColumn($table, $column)) {
                return null;
            }

            $query = $value === null ? $query->whereNull($column) : $query->where($column, $value);
        }

        $id = $query->value('id');

        return $id === null ? null : (int) $id;
    }

    /**
     * @param  array<string, mixed>  $criteria
     * @param  array<string, mixed>  $values
     */
    protected function upsert(string $table, array $criteria, array $values): void
    {
        if (! Schema::hasTable($table) || ! $this->hasColumns($table, array_keys($criteria))) {
            return;
        }

        $payload = $this->onlyColumns($table, $values + [
            'row_version' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table($table)->updateOrInsert($criteria, $payload);
    }

    /**
     * @param  list<string>  $columns
     */
    protected function hasColumns(string $table, array $columns): bool
    {
        foreach ($columns as $column) {
            if (! Schema::hasColumn($table, $column)) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param  array<string, mixed>  $values
     * @return array<string, mixed>
     */
    protected function onlyColumns(string $table, array $values): array
    {
        $columns = array_flip(Schema::getColumnListing($table));

        return array_intersect_key($values, $columns);
    }

    /**
     * @param  array<string, mixed>  $value
     */
    protected function json(array $value): string
    {
        return (string) json_encode($value, JSON_THROW_ON_ERROR);
    }

    protected function seedMetadata(string $source, string $scenario = 'reference'): string
    {
        return $this->json([
            'seed_source' => $source,
            'seed_scenario' => $scenario,
        ]);
    }
}
