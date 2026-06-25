<?php

declare(strict_types=1);

namespace Modules\Configuration\Repositories;

use DateTimeImmutable;
use DateTimeInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use InvalidArgumentException;
use Modules\Configuration\Constants\ConfigurationScope;
use Modules\Configuration\Contracts\ConfigurationRevisionRepositoryInterface;
use Modules\Configuration\Data\ConfigurationRevisionView;
use Modules\Configuration\Data\ConfigurationScopeContext;
use Modules\Configuration\Models\ConfigurationValueRevision;

final class EloquentConfigurationRevisionRepository implements ConfigurationRevisionRepositoryInterface
{
    public function record(
        ConfigurationScopeContext $context,
        array $attributes,
    ): ConfigurationRevisionView {
        $model = ConfigurationValueRevision::query()->create([
            'scope' => $context->scope,
            'tenant_id' => $context->tenantId,
            'organization_unit_id' => $context->organizationUnitId,
            ...$attributes,
            'created_at' => now(),
        ]);

        return $this->map($model);
    }

    public function paginate(
        ConfigurationScopeContext $context,
        string $key,
        int $page,
        int $perPage,
    ): LengthAwarePaginator {
        $paginator = $this->scopedQuery($context)
            ->where('key', strtolower(trim($key)))
            ->orderByDesc('id')
            ->paginate(
                min(max($perPage, 1), 100),
                ['*'],
                'page',
                max($page, 1),
            );
        $paginator->setCollection($paginator->getCollection()->map(
            fn (ConfigurationValueRevision $revision): ConfigurationRevisionView => $this->map($revision),
        ));

        return $paginator;
    }

    /** @return Builder<ConfigurationValueRevision> */
    private function scopedQuery(ConfigurationScopeContext $context): Builder
    {
        $query = ConfigurationValueRevision::query()->where('scope', $context->scope);

        return match ($context->scope) {
            ConfigurationScope::GLOBAL => $query
                ->whereNull('tenant_id')
                ->whereNull('organization_unit_id'),
            ConfigurationScope::TENANT => $query
                ->where('tenant_id', $this->requiredTenantId($context))
                ->whereNull('organization_unit_id'),
            ConfigurationScope::ORGANIZATION_UNIT => $query
                ->where('tenant_id', $this->requiredTenantId($context))
                ->where('organization_unit_id', $this->requiredOrganizationUnitId($context)),
            default => throw new InvalidArgumentException('Unsupported configuration scope.'),
        };
    }

    private function requiredTenantId(ConfigurationScopeContext $context): int
    {
        return $context->tenantId
            ?? throw new InvalidArgumentException('Tenant scope requires a tenant ID.');
    }

    private function requiredOrganizationUnitId(ConfigurationScopeContext $context): int
    {
        return $context->organizationUnitId
            ?? throw new InvalidArgumentException('Organization-unit scope requires an organization-unit ID.');
    }

    private function map(ConfigurationValueRevision $model): ConfigurationRevisionView
    {
        $createdAt = $model->getAttribute('created_at');

        return new ConfigurationRevisionView(
            id: (int) $model->getKey(),
            scope: (string) $model->getAttribute('scope'),
            key: (string) $model->getAttribute('key'),
            action: (string) $model->getAttribute('action'),
            valueType: (string) $model->getAttribute('value_type'),
            sensitive: (bool) $model->getAttribute('is_sensitive'),
            beforeExists: (bool) $model->getAttribute('before_exists'),
            beforeValue: $model->getAttribute('before_value'),
            afterExists: (bool) $model->getAttribute('after_exists'),
            afterValue: $model->getAttribute('after_value'),
            entryRowVersion: (int) $model->getAttribute('entry_row_version'),
            changedBy: is_numeric($model->getAttribute('changed_by'))
                ? (int) $model->getAttribute('changed_by')
                : null,
            changedByName: $model->getAttribute('changed_by_name') !== null
                ? (string) $model->getAttribute('changed_by_name')
                : null,
            createdAt: $createdAt instanceof DateTimeInterface
                ? DateTimeImmutable::createFromInterface($createdAt)
                : new DateTimeImmutable((string) $createdAt),
        );
    }
}
