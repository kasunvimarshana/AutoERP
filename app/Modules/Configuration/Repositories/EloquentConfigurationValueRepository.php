<?php

declare(strict_types=1);

namespace Modules\Configuration\Repositories;

use DateTimeImmutable;
use DateTimeInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;
use InvalidArgumentException;
use Modules\Configuration\Constants\ConfigurationScope;
use Modules\Configuration\Contracts\ConfigurationValueRepositoryInterface;
use Modules\Configuration\Data\ConfigurationScopeContext;
use Modules\Configuration\Data\StoredConfigurationValue;
use Modules\Configuration\Models\GlobalConfigurationValue;
use Modules\Configuration\Models\OrganizationUnitConfigurationValue;
use Modules\Configuration\Models\TenantConfigurationValue;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

final class EloquentConfigurationValueRepository implements ConfigurationValueRepositoryInterface
{
    public function findExact(
        ConfigurationScopeContext $context,
        string $key,
    ): ?StoredConfigurationValue {
        $model = $this->scopedQuery($context)
            ->where('key', strtolower(trim($key)))
            ->first();

        return $model instanceof Model
            ? $this->map($model, $context->scope)
            : null;
    }

    public function paginate(
        ConfigurationScopeContext $context,
        array $keys,
        int $page,
        int $perPage,
    ): LengthAwarePaginator {
        $query = $this->scopedQuery($context);
        $keys === [] ? $query->whereRaw('1 = 0') : $query->whereIn('key', $keys);

        $paginator = $query
            ->orderBy('key')
            ->orderBy('id')
            ->paginate(
                min(max($perPage, 1), 100),
                ['*'],
                'page',
                max($page, 1),
            );

        $paginator->setCollection(
            $paginator->getCollection()->map(
                fn (Model $model): StoredConfigurationValue =>
                    $this->map($model, $context->scope),
            ),
        );

        return $paginator;
    }

    public function keys(ConfigurationScopeContext $context): array
    {
        return $this->scopedQuery($context)
            ->orderBy('key')
            ->pluck('key')
            ->map(static fn (mixed $key): string => (string) $key)
            ->all();
    }

    public function create(
        ConfigurationScopeContext $context,
        array $attributes,
    ): StoredConfigurationValue {
        $class = $this->modelClass($context->scope);
        $model = new $class();
        $model->fill([
            ...$this->scopeAttributes($context),
            ...$attributes,
        ]);
        $model->setAttribute('row_version', 1);
        $model->save();

        return $this->map($model->refresh(), $context->scope);
    }

    public function updateExpected(
        StoredConfigurationValue $current,
        int $expectedVersion,
        array $attributes,
    ): StoredConfigurationValue {
        $updated = $this->queryForStored($current)
            ->whereKey($current->id)
            ->where('row_version', $expectedVersion)
            ->update([
                ...$attributes,
                'row_version' => $expectedVersion + 1,
                'updated_at' => now(),
            ]);

        if ($updated !== 1) {
            throw new ConflictHttpException(
                'This setting changed after it was loaded. Refresh and try again.',
            );
        }

        $model = $this->queryForStored($current)->find($current->id);
        if (! $model instanceof Model) {
            throw new NotFoundHttpException('Configuration value was not found.');
        }

        return $this->map($model, $current->scope);
    }

    public function deleteExpected(
        StoredConfigurationValue $current,
        int $expectedVersion,
    ): void {
        $deleted = $this->queryForStored($current)
            ->whereKey($current->id)
            ->where('row_version', $expectedVersion)
            ->delete();

        if ($deleted !== 1) {
            throw new ConflictHttpException(
                'This setting changed after it was loaded. Refresh and try again.',
            );
        }
    }

    /** @return Builder<Model> */
    private function scopedQuery(ConfigurationScopeContext $context): Builder
    {
        $class = $this->modelClass($context->scope);
        $query = $class::query();

        return match ($context->scope) {
            ConfigurationScope::GLOBAL => $query,
            ConfigurationScope::TENANT => $query->where(
                'tenant_id',
                $this->requiredTenantId($context),
            ),
            ConfigurationScope::ORGANIZATION_UNIT => $query
                ->where('tenant_id', $this->requiredTenantId($context))
                ->where(
                    'organization_unit_id',
                    $this->requiredOrganizationUnitId($context),
                ),
            default => throw new InvalidArgumentException('Unsupported configuration scope.'),
        };
    }

    /** @return Builder<Model> */
    private function queryForStored(StoredConfigurationValue $stored): Builder
    {
        return $this->scopedQuery(new ConfigurationScopeContext(
            scope: $stored->scope,
            tenantId: $stored->tenantId,
            organizationUnitId: $stored->organizationUnitId,
        ));
    }

    /** @return class-string<Model> */
    private function modelClass(string $scope): string
    {
        return match ($scope) {
            ConfigurationScope::GLOBAL => GlobalConfigurationValue::class,
            ConfigurationScope::TENANT => TenantConfigurationValue::class,
            ConfigurationScope::ORGANIZATION_UNIT => OrganizationUnitConfigurationValue::class,
            default => throw new InvalidArgumentException('Unsupported configuration scope.'),
        };
    }

    /** @return array<string, int> */
    private function scopeAttributes(ConfigurationScopeContext $context): array
    {
        return match ($context->scope) {
            ConfigurationScope::GLOBAL => [],
            ConfigurationScope::TENANT => [
                'tenant_id' => $this->requiredTenantId($context),
            ],
            ConfigurationScope::ORGANIZATION_UNIT => [
                'tenant_id' => $this->requiredTenantId($context),
                'organization_unit_id' => $this->requiredOrganizationUnitId($context),
            ],
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
            ?? throw new InvalidArgumentException(
                'Organization-unit scope requires an organization-unit ID.',
            );
    }

    private function map(Model $model, string $scope): StoredConfigurationValue
    {
        $updatedAt = $model->getAttribute('updated_at');

        return new StoredConfigurationValue(
            id: (int) $model->getKey(),
            scope: $scope,
            tenantId: is_numeric($model->getAttribute('tenant_id'))
                ? (int) $model->getAttribute('tenant_id')
                : null,
            organizationUnitId: is_numeric($model->getAttribute('organization_unit_id'))
                ? (int) $model->getAttribute('organization_unit_id')
                : null,
            key: (string) $model->getAttribute('key'),
            storedValue: $model->getAttribute('value') !== null
                ? (string) $model->getAttribute('value')
                : null,
            valueType: (string) $model->getAttribute('value_type'),
            sensitive: (bool) $model->getAttribute('is_sensitive'),
            rowVersion: (int) $model->getAttribute('row_version'),
            updatedAt: $updatedAt instanceof DateTimeInterface
                ? DateTimeImmutable::createFromInterface($updatedAt)
                : new DateTimeImmutable((string) $updatedAt),
        );
    }
}
