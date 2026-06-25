<?php

declare(strict_types=1);

namespace Modules\Configuration\Services;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Modules\Configuration\Constants\ConfigurationScope;
use Modules\Configuration\Data\ConfigurationScopeContext;
use Modules\Configuration\Data\StoredConfigurationValue;
use Modules\Configuration\Models\ConfigurationValueRevision;
use Modules\Configuration\Models\GlobalConfigurationValueRevision;
use Modules\Configuration\Models\OrganizationUnitConfigurationValueRevision;
use Modules\Configuration\Models\TenantConfigurationValueRevision;
use Modules\Core\Contracts\ClockInterface;
use Modules\Core\Contracts\CurrentUserContextAccessorInterface;

final class ConfigurationRevisionService
{
    public function __construct(
        private readonly GlobalConfigurationValueRevision $globalRevisions,
        private readonly TenantConfigurationValueRevision $tenantRevisions,
        private readonly OrganizationUnitConfigurationValueRevision $organizationUnitRevisions,
        private readonly CurrentUserContextAccessorInterface $currentUser,
        private readonly ClockInterface $clock,
    ) {}

    public function record(
        ConfigurationScopeContext $context,
        string $key,
        string $operation,
        ?StoredConfigurationValue $result,
        int $definitionVersion,
        string $valueType,
        bool $sensitive,
        ?int $sourceRevisionId = null,
        ?string $reason = null,
    ): ConfigurationValueRevision {
        $attributes = [
            ...$this->scopeIdentity($context),
            'key' => strtolower(trim($key)),
            'definition_version' => $definitionVersion,
            'operation' => $operation,
            'stored_value' => $result?->storedValue,
            'value_type' => $valueType,
            'is_sensitive' => $sensitive,
            'resulting_row_version' => $result?->rowVersion,
            'source_revision_id' => $sourceRevisionId,
            'actor_user_id' => $this->currentUser->currentUserId(),
            'reason' => $this->nullableReason($reason),
            'created_at' => $this->clock->now(),
        ];

        return $this->modelFor($context)->newQuery()->create($attributes);
    }

    /** @return LengthAwarePaginator<int, ConfigurationValueRevision> */
    public function page(ConfigurationScopeContext $context, string $key, int $page, int $perPage): LengthAwarePaginator
    {
        return $this->scopeQuery($context)
            ->where('key', strtolower(trim($key)))
            ->with('actor:id,first_name,last_name,email,platform_login_email')
            ->orderByDesc('id')
            ->paginate(min(max($perPage, 1), 100), ['*'], 'page', max($page, 1));
    }

    public function find(ConfigurationScopeContext $context, int $revisionId, string $key): ConfigurationValueRevision
    {
        $revision = $this->scopeQuery($context)
            ->whereKey($revisionId)
            ->where('key', strtolower(trim($key)))
            ->lockForUpdate()
            ->first();

        return $revision instanceof ConfigurationValueRevision
            ? $revision
            : throw (new ModelNotFoundException())->setModel(ConfigurationValueRevision::class, [$revisionId]);
    }

    /** @return Builder<ConfigurationValueRevision> */
    private function scopeQuery(ConfigurationScopeContext $context): Builder
    {
        return $this->modelFor($context)->newQuery()
            ->when(
                $context->tenantId !== null,
                fn (Builder $query): Builder => $query->where('tenant_id', $context->tenantId),
            )
            ->when(
                $context->organizationUnitId !== null,
                fn (Builder $query): Builder => $query->where('organization_unit_id', $context->organizationUnitId),
            );
    }

    private function modelFor(ConfigurationScopeContext $context): ConfigurationValueRevision
    {
        return match ($context->scope) {
            ConfigurationScope::GLOBAL => $this->globalRevisions,
            ConfigurationScope::TENANT => $this->tenantRevisions,
            ConfigurationScope::ORGANIZATION_UNIT => $this->organizationUnitRevisions,
        };
    }

    /** @return array<string, int> */
    private function scopeIdentity(ConfigurationScopeContext $context): array
    {
        return match ($context->scope) {
            ConfigurationScope::GLOBAL => [],
            ConfigurationScope::TENANT => ['tenant_id' => (int) $context->tenantId],
            ConfigurationScope::ORGANIZATION_UNIT => [
                'tenant_id' => (int) $context->tenantId,
                'organization_unit_id' => (int) $context->organizationUnitId,
            ],
        };
    }

    private function nullableReason(?string $reason): ?string
    {
        $reason = trim((string) $reason);

        return $reason === '' ? null : mb_substr($reason, 0, 1000);
    }
}
