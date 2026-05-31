<?php

declare(strict_types=1);

namespace Modules\Core\Infrastructure\Persistence\Eloquent\Repositories;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Modules\Core\Application\DTO\DataRecord;
use Modules\Core\Application\Repositories\BusinessPartyLinkRepositoryInterface;
use Modules\Core\Infrastructure\Persistence\Eloquent\Models\BusinessPartyLinkModel;

final class EloquentBusinessPartyLinkRepository extends EloquentRepository implements BusinessPartyLinkRepositoryInterface
{
    public function __construct(BusinessPartyLinkModel $model)
    {
        parent::__construct($model);
    }

    public function listForSource(int $tenantId, string $sourcePartyType, ?int $sourcePartyId): array
    {
        $query = $this->query()
            ->where('tenant_id', $tenantId)
            ->where('source_party_type', $sourcePartyType)
            ->orderByDesc('is_active')
            ->orderByDesc('id');

        $sourcePartyId === null
            ? $query->whereNull('source_party_id')
            : $query->where('source_party_id', $sourcePartyId);

        return $this->recordsFromModels($query->get()->all());
    }

    public function listForTarget(int $tenantId, string $targetPartyType, ?int $targetPartyId): array
    {
        $query = $this->query()
            ->where('tenant_id', $tenantId)
            ->where('target_party_type', $targetPartyType)
            ->orderByDesc('is_active')
            ->orderByDesc('id');

        $targetPartyId === null
            ? $query->whereNull('target_party_id')
            : $query->where('target_party_id', $targetPartyId);

        return $this->recordsFromModels($query->get()->all());
    }

    public function findInTenant(int $tenantId, int $linkId): ?DataRecord
    {
        $model = $this->query()
            ->where('tenant_id', $tenantId)
            ->where('id', $linkId)
            ->first();

        return $model instanceof Model ? $this->toRecord($model) : null;
    }

    public function activeDuplicateExists(
        int $tenantId,
        string $sourcePartyType,
        ?int $sourcePartyId,
        string $targetPartyType,
        ?int $targetPartyId,
        string $relationType,
        ?int $exceptLinkId = null,
    ): bool {
        $query = $this->query()
            ->where('tenant_id', $tenantId)
            ->where('source_party_type', $sourcePartyType)
            ->where('target_party_type', $targetPartyType)
            ->where('relation_type', $relationType)
            ->where('is_active', true);

        $sourcePartyId === null
            ? $query->whereNull('source_party_id')
            : $query->where('source_party_id', $sourcePartyId);

        $targetPartyId === null
            ? $query->whereNull('target_party_id')
            : $query->where('target_party_id', $targetPartyId);

        if ($exceptLinkId !== null) {
            $query->where('id', '!=', $exceptLinkId);
        }

        return $query->exists();
    }

    public function partyReferenceExists(string $partyType, int $partyId, int $tenantId): bool
    {
        $table = match ($partyType) {
            'customer' => 'customers',
            'supplier', 'provider' => 'suppliers',
            'employee' => 'employees',
            'user' => 'users',
            'party' => 'parties',
            default => null,
        };

        if ($table === null || ! Schema::hasTable($table)) {
            return false;
        }

        if ($partyType === 'user') {
            $query = DB::table('users')->where('users.id', $partyId);

            if (Schema::hasColumn('users', 'deleted_at')) {
                $query->whereNull('users.deleted_at');
            }

            if (Schema::hasTable('user_tenants')) {
                $query->whereExists(static function ($subQuery) use ($partyId, $tenantId): void {
                    $subQuery->selectRaw('1')
                        ->from('user_tenants')
                        ->whereColumn('user_tenants.user_id', 'users.id')
                        ->where('user_tenants.user_id', $partyId)
                        ->where('user_tenants.tenant_id', $tenantId);
                });

                return $query->exists();
            }

            return Schema::hasColumn('users', 'tenant_id')
                ? $query->where('users.tenant_id', $tenantId)->exists()
                : false;
        }

        $query = DB::table($table)->where('id', $partyId);

        if (Schema::hasColumn($table, 'tenant_id')) {
            $query->where('tenant_id', $tenantId);
        }

        if (Schema::hasColumn($table, 'deleted_at')) {
            $query->whereNull('deleted_at');
        }

        return $query->exists();
    }

    /**
     * @param list<mixed> $models
     * @return list<DataRecord>
     */
    private function recordsFromModels(array $models): array
    {
        $records = [];
        foreach ($models as $model) {
            if ($model instanceof Model) {
                $records[] = $this->toRecord($model);
            }
        }

        return $records;
    }
}
