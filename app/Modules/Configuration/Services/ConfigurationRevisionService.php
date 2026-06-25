<?php

declare(strict_types=1);

namespace Modules\Configuration\Services;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Modules\Configuration\Data\ConfigurationScopeContext;
use Modules\Configuration\Data\StoredConfigurationValue;
use Modules\Configuration\Models\ConfigurationValueRevision;
use Modules\Core\Contracts\ClockInterface;
use Modules\Core\Contracts\CurrentUserContextAccessorInterface;

final class ConfigurationRevisionService
{
    public function __construct(
        private readonly ConfigurationValueRevision $revisions,
        private readonly CurrentUserContextAccessorInterface $currentUser,
        private readonly ClockInterface $clock,
    ) {}

    public function record(
        ConfigurationScopeContext $context,
        string $key,
        string $operation,
        ?StoredConfigurationValue $result,
        string $valueType,
        bool $sensitive,
        ?int $sourceRevisionId = null,
        ?string $reason = null,
    ): ConfigurationValueRevision {
        return $this->revisions->newQuery()->create([
            'scope' => $context->scope,
            'tenant_id' => $context->tenantId,
            'organization_unit_id' => $context->organizationUnitId,
            'key' => strtolower(trim($key)),
            'operation' => $operation,
            'stored_value' => $result?->storedValue,
            'value_type' => $valueType,
            'is_sensitive' => $sensitive,
            'resulting_row_version' => $result?->rowVersion,
            'source_revision_id' => $sourceRevisionId,
            'actor_user_id' => $this->currentUser->currentUserId(),
            'reason' => $this->nullableReason($reason),
            'created_at' => $this->clock->now(),
        ]);
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
        return $this->revisions->newQuery()
            ->where('scope', $context->scope)
            ->when(
                $context->tenantId === null,
                fn (Builder $query): Builder => $query->whereNull('tenant_id'),
                fn (Builder $query): Builder => $query->where('tenant_id', $context->tenantId),
            )
            ->when(
                $context->organizationUnitId === null,
                fn (Builder $query): Builder => $query->whereNull('organization_unit_id'),
                fn (Builder $query): Builder => $query->where('organization_unit_id', $context->organizationUnitId),
            );
    }

    private function nullableReason(?string $reason): ?string
    {
        $reason = trim((string) $reason);

        return $reason === '' ? null : mb_substr($reason, 0, 1000);
    }
}
