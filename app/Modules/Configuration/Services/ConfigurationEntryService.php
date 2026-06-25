<?php

declare(strict_types=1);

namespace Modules\Configuration\Services;

use DateTimeImmutable;
use DateTimeInterface;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\QueryException;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Modules\Audit\Constants\AuditEventCategory;
use Modules\Audit\Contracts\AuditRecorderInterface;
use Modules\Audit\Data\AuditEventData;
use Modules\Configuration\Constants\ConfigurationRevisionOperation;
use Modules\Configuration\Constants\ConfigurationScope;
use Modules\Configuration\Contracts\ConfigurationDefinitionRegistryInterface;
use Modules\Configuration\Contracts\ConfigurationTargetValidatorInterface;
use Modules\Configuration\Contracts\ConfigurationValueRepositoryInterface;
use Modules\Configuration\Data\ConfigurationDefinition;
use Modules\Configuration\Data\ConfigurationEntryView;
use Modules\Configuration\Data\ConfigurationRevisionView;
use Modules\Configuration\Data\ConfigurationScopeContext;
use Modules\Configuration\Data\ResolvedConfigurationValue;
use Modules\Configuration\Data\StoredConfigurationValue;
use Modules\Configuration\Models\ConfigurationValueRevision;
use Modules\Core\Contracts\CurrentOrganizationUnitContextAccessorInterface;
use Modules\Core\Contracts\CurrentTenantContextAccessorInterface;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

final class ConfigurationEntryService
{
    public function __construct(
        private readonly ConfigurationDefinitionRegistryInterface $definitions,
        private readonly ConfigurationValueRepositoryInterface $repository,
        private readonly ConfigurationScopeResolver $scopes,
        private readonly ConfigurationTargetValidatorInterface $targets,
        private readonly ConfigurationValueValidator $validator,
        private readonly ConfigurationValueCodec $codec,
        private readonly ResolveConfiguration $resolver,
        private readonly ConfigurationAuthorizationService $authorization,
        private readonly ConfigurationRevisionService $revisions,
        private readonly CurrentTenantContextAccessorInterface $currentTenant,
        private readonly CurrentOrganizationUnitContextAccessorInterface $currentOrganizationUnit,
        private readonly AuditRecorderInterface $audit,
    ) {}

    /** @return LengthAwarePaginator<int, ConfigurationEntryView> */
    public function list(
        string $scope,
        ?string $search,
        ?string $owner,
        int $page,
        int $perPage,
    ): LengthAwarePaginator {
        $this->assertCanView($scope, false);

        return $this->listInContext($this->scopes->current($scope), $search, $owner, $page, $perPage);
    }

    /** @return LengthAwarePaginator<int, ConfigurationEntryView> */
    public function listPlatform(
        string $scope,
        ?int $tenantId,
        ?int $organizationUnitId,
        ?string $search,
        ?string $owner,
        int $page,
        int $perPage,
    ): LengthAwarePaginator {
        $this->assertCanView($scope, true);

        return $this->listInContext(
            $this->platformContext($scope, $tenantId, $organizationUnitId, false),
            $search,
            $owner,
            $page,
            $perPage,
        );
    }

    /** @return list<string> */
    public function existingKeys(string $scope): array
    {
        $this->assertCanView($scope, false);

        return $this->repository->keys($this->scopes->current($scope));
    }

    /** @return list<string> */
    public function existingKeysPlatform(string $scope, ?int $tenantId, ?int $organizationUnitId): array
    {
        $this->assertCanView($scope, true);

        return $this->repository->keys(
            $this->platformContext($scope, $tenantId, $organizationUnitId, false),
        );
    }

    public function exact(string $scope, string $key): ConfigurationEntryView
    {
        $this->assertCanView($scope, false);

        return $this->exactInContext($this->scopes->current($scope), $key);
    }

    public function exactPlatform(
        string $scope,
        ?int $tenantId,
        ?int $organizationUnitId,
        string $key,
    ): ConfigurationEntryView {
        $this->assertCanView($scope, true);

        return $this->exactInContext(
            $this->platformContext($scope, $tenantId, $organizationUnitId, false),
            $key,
        );
    }

    public function resolvedCurrent(string $key): ResolvedConfigurationValue
    {
        $this->assertCanView(ConfigurationScope::TENANT, false);

        return $this->resolver->resolve(
            strtolower(trim($key)),
            $this->currentTenant->requireCurrent()->tenantId(),
            $this->currentOrganizationUnit->currentOrganizationUnitId(),
        );
    }

    public function resolvedPlatform(
        string $key,
        int $tenantId,
        ?int $organizationUnitId,
    ): ResolvedConfigurationValue {
        $this->assertCanView(ConfigurationScope::TENANT, true);
        $this->targets->assertTenantReadable($tenantId);
        if ($organizationUnitId !== null) {
            $this->scopes->explicit(
                ConfigurationScope::ORGANIZATION_UNIT,
                $tenantId,
                $organizationUnitId,
                false,
            );
        }

        return $this->resolver->resolve(strtolower(trim($key)), $tenantId, $organizationUnitId);
    }

    public function create(string $scope, string $key, mixed $value): ConfigurationEntryView
    {
        return $this->createInContext($this->scopes->current($scope), $key, $value, false);
    }

    public function createPlatform(
        string $scope,
        ?int $tenantId,
        ?int $organizationUnitId,
        string $key,
        mixed $value,
    ): ConfigurationEntryView {
        return $this->createInContext(
            $this->platformContext($scope, $tenantId, $organizationUnitId, true),
            $key,
            $value,
            true,
        );
    }

    public function update(
        string $scope,
        string $key,
        int $expectedVersion,
        mixed $value,
    ): ConfigurationEntryView {
        return $this->updateInContext(
            $this->scopes->current($scope),
            $key,
            $expectedVersion,
            $value,
            false,
        );
    }

    public function updatePlatform(
        string $scope,
        ?int $tenantId,
        ?int $organizationUnitId,
        string $key,
        int $expectedVersion,
        mixed $value,
    ): ConfigurationEntryView {
        return $this->updateInContext(
            $this->platformContext($scope, $tenantId, $organizationUnitId, true),
            $key,
            $expectedVersion,
            $value,
            true,
        );
    }

    public function delete(string $scope, string $key, int $expectedVersion): void
    {
        $this->deleteInContext($this->scopes->current($scope), $key, $expectedVersion, false);
    }

    public function deletePlatform(
        string $scope,
        ?int $tenantId,
        ?int $organizationUnitId,
        string $key,
        int $expectedVersion,
    ): void {
        $this->deleteInContext(
            $this->platformContext($scope, $tenantId, $organizationUnitId, true),
            $key,
            $expectedVersion,
            true,
        );
    }

    /** @return LengthAwarePaginator<int, ConfigurationRevisionView> */
    public function history(string $scope, string $key, int $page, int $perPage): LengthAwarePaginator
    {
        $this->assertCanView($scope, false);

        return $this->historyInContext($this->scopes->current($scope), $key, $page, $perPage);
    }

    /** @return LengthAwarePaginator<int, ConfigurationRevisionView> */
    public function historyPlatform(
        string $scope,
        ?int $tenantId,
        ?int $organizationUnitId,
        string $key,
        int $page,
        int $perPage,
    ): LengthAwarePaginator {
        $this->assertCanView($scope, true);

        return $this->historyInContext(
            $this->platformContext($scope, $tenantId, $organizationUnitId, false),
            $key,
            $page,
            $perPage,
        );
    }

    public function rollback(
        string $scope,
        string $key,
        int $revisionId,
        int $expectedVersion,
        string $reason,
    ): ?ConfigurationEntryView {
        return $this->rollbackInContext(
            $this->scopes->current($scope),
            $key,
            $revisionId,
            $expectedVersion,
            $reason,
            false,
        );
    }

    public function rollbackPlatform(
        string $scope,
        ?int $tenantId,
        ?int $organizationUnitId,
        string $key,
        int $revisionId,
        int $expectedVersion,
        string $reason,
    ): ?ConfigurationEntryView {
        return $this->rollbackInContext(
            $this->platformContext($scope, $tenantId, $organizationUnitId, true),
            $key,
            $revisionId,
            $expectedVersion,
            $reason,
            true,
        );
    }

    /** @return LengthAwarePaginator<int, ConfigurationEntryView> */
    private function listInContext(
        ConfigurationScopeContext $context,
        ?string $search,
        ?string $owner,
        int $page,
        int $perPage,
    ): LengthAwarePaginator {
        $keys = $this->matchingDefinitionKeys($context->scope, $search, $owner);
        $paginator = $this->repository->paginate($context, $keys, $page, $perPage);
        $paginator->setCollection($paginator->getCollection()->map(
            fn (StoredConfigurationValue $stored): ConfigurationEntryView => $this->view($stored),
        ));

        return $paginator;
    }

    private function exactInContext(ConfigurationScopeContext $context, string $key): ConfigurationEntryView
    {
        $stored = $this->resolver->exact($context, strtolower(trim($key)));

        return $stored !== null
            ? $this->view($stored)
            : throw new NotFoundHttpException('The configuration override was not found in the selected scope.');
    }

    private function createInContext(
        ConfigurationScopeContext $context,
        string $key,
        mixed $value,
        bool $platform,
    ): ConfigurationEntryView {
        $definition = $this->definitionForMutation($context, $key, $platform);
        $normalized = $this->validator->validate($definition, $value);

        $stored = DB::transaction(function () use ($context, $definition, $normalized): StoredConfigurationValue {
            if ($this->repository->findExact($context, $definition->key) !== null) {
                throw new ConflictHttpException('A configuration override already exists in the selected scope.');
            }

            try {
                $created = $this->repository->create($context, [
                    'key' => $definition->key,
                    'definition_version' => $definition->version,
                    'value' => $this->codec->encode($definition, $normalized),
                    'value_type' => $definition->valueType,
                    'is_sensitive' => $definition->sensitive,
                ]);
            } catch (QueryException $exception) {
                if (! $this->isUniqueConstraintViolation($exception)) {
                    throw $exception;
                }

                throw new ConflictHttpException(
                    'A configuration override already exists in the selected scope.',
                    previous: $exception,
                );
            }

            $this->revisions->record(
                $context,
                $definition->key,
                ConfigurationRevisionOperation::CREATED,
                $created,
                $definition->version,
                $definition->valueType,
                $definition->sensitive,
            );
            $this->recordChange('created', $definition, $created, null, $normalized);

            return $created;
        }, 3);

        $this->resolver->forget($context, $definition->key);

        return $this->view($stored);
    }

    private function updateInContext(
        ConfigurationScopeContext $context,
        string $key,
        int $expectedVersion,
        mixed $value,
        bool $platform,
    ): ConfigurationEntryView {
        $definition = $this->definitionForMutation($context, $key, $platform);
        $normalized = $this->validator->validate($definition, $value);

        $stored = DB::transaction(function () use (
            $context,
            $definition,
            $expectedVersion,
            $normalized,
        ): StoredConfigurationValue {
            $current = $this->repository->findExact($context, $definition->key)
                ?? throw new NotFoundHttpException('The configuration override was not found in the selected scope.');

            if ($current->rowVersion !== $expectedVersion) {
                throw new ConflictHttpException('This setting changed after it was loaded. Refresh and try again.');
            }

            $before = $this->codec->decode($definition, $current->storedValue);
            if ($before === $normalized) {
                return $current;
            }

            $updated = $this->repository->updateExpected($current, $expectedVersion, [
                'value' => $this->codec->encode($definition, $normalized),
                'definition_version' => $definition->version,
                'value_type' => $definition->valueType,
                'is_sensitive' => $definition->sensitive,
            ]);
            $this->revisions->record(
                $context,
                $definition->key,
                ConfigurationRevisionOperation::UPDATED,
                $updated,
                $definition->version,
                $definition->valueType,
                $definition->sensitive,
            );
            $this->recordChange('updated', $definition, $updated, $before, $normalized);

            return $updated;
        }, 3);

        $this->resolver->forget($context, $definition->key);

        return $this->view($stored);
    }

    private function deleteInContext(
        ConfigurationScopeContext $context,
        string $key,
        int $expectedVersion,
        bool $platform,
    ): void {
        $definition = $this->definitionForMutation($context, $key, $platform);

        DB::transaction(function () use ($context, $definition, $expectedVersion): void {
            $current = $this->repository->findExact($context, $definition->key)
                ?? throw new NotFoundHttpException('The configuration override was not found in the selected scope.');
            $before = $this->codec->decode($definition, $current->storedValue);
            $this->repository->deleteExpected($current, $expectedVersion);
            $this->revisions->record(
                $context,
                $definition->key,
                ConfigurationRevisionOperation::REMOVED,
                null,
                $definition->version,
                $definition->valueType,
                $definition->sensitive,
            );
            $this->recordChange('removed', $definition, $current, $before, null);
        }, 3);

        $this->resolver->forget($context, $definition->key);
    }

    /** @return LengthAwarePaginator<int, ConfigurationRevisionView> */
    private function historyInContext(
        ConfigurationScopeContext $context,
        string $key,
        int $page,
        int $perPage,
    ): LengthAwarePaginator {
        $definition = $this->definitions->get(strtolower(trim($key)));
        if (! in_array($context->scope, $definition->allowedScopes, true)) {
            throw new NotFoundHttpException('The configuration definition is not available in this scope.');
        }

        $paginator = $this->revisions->page($context, $definition->key, $page, $perPage);
        $paginator->setCollection($paginator->getCollection()->map(
            fn (ConfigurationValueRevision $revision): ConfigurationRevisionView => $this->revisionView($definition, $revision),
        ));

        return $paginator;
    }

    private function rollbackInContext(
        ConfigurationScopeContext $context,
        string $key,
        int $revisionId,
        int $expectedVersion,
        string $reason,
        bool $platform,
    ): ?ConfigurationEntryView {
        $definition = $this->definitionForMutation($context, $key, $platform);
        $reason = trim($reason);
        if ($reason === '') {
            throw ValidationException::withMessages(['reason' => ['A rollback reason is required.']]);
        }

        $stored = DB::transaction(function () use (
            $context,
            $definition,
            $revisionId,
            $expectedVersion,
            $reason,
        ): ?StoredConfigurationValue {
            $target = $this->revisions->find($context, $revisionId, $definition->key);
            if (
                (int) $target->getAttribute('definition_version') !== $definition->version
                || (string) $target->getAttribute('value_type') !== $definition->valueType
                || (bool) $target->getAttribute('is_sensitive') !== $definition->sensitive
            ) {
                throw new ConflictHttpException('This historical value is incompatible with the current definition.');
            }

            $current = $this->repository->findExact($context, $definition->key);
            $targetStoredValue = $target->getAttribute('stored_value');
            $targetStoredValue = $targetStoredValue === null ? null : (string) $targetStoredValue;
            $before = $current === null ? null : $this->codec->decode($definition, $current->storedValue);

            if ($targetStoredValue === null) {
                if ($current === null) {
                    if ($expectedVersion !== 0) {
                        throw new ConflictHttpException('This setting changed after it was loaded. Refresh and try again.');
                    }

                    throw new ConflictHttpException('The selected revision already matches the current unconfigured state.');
                }
                $this->repository->deleteExpected($current, $expectedVersion);
                $result = null;
                $after = null;
            } else {
                $after = $this->codec->decode($definition, $targetStoredValue);
                if ($current === null) {
                    if ($expectedVersion !== 0) {
                        throw new ConflictHttpException('This setting changed after it was loaded. Refresh and try again.');
                    }
                    $result = $this->repository->create($context, [
                        'key' => $definition->key,
                        'definition_version' => $definition->version,
                        'value' => $targetStoredValue,
                        'value_type' => $definition->valueType,
                        'is_sensitive' => $definition->sensitive,
                    ]);
                } else {
                    if ($current->storedValue === $targetStoredValue) {
                        throw new ConflictHttpException('The selected revision already matches the current value.');
                    }
                    $result = $this->repository->updateExpected($current, $expectedVersion, [
                        'value' => $targetStoredValue,
                        'definition_version' => $definition->version,
                        'value_type' => $definition->valueType,
                        'is_sensitive' => $definition->sensitive,
                    ]);
                }
            }

            $this->revisions->record(
                $context,
                $definition->key,
                ConfigurationRevisionOperation::ROLLED_BACK,
                $result,
                $definition->version,
                $definition->valueType,
                $definition->sensitive,
                (int) $target->getKey(),
                $reason,
            );
            $auditStored = $result ?? $current;
            if ($auditStored instanceof StoredConfigurationValue) {
                $this->recordChange('rolled_back', $definition, $auditStored, $before, $after);
            }

            return $result;
        }, 3);

        $this->resolver->forget($context, $definition->key);

        return $stored === null ? null : $this->view($stored);
    }

    private function platformContext(
        string $scope,
        ?int $tenantId,
        ?int $organizationUnitId,
        bool $writable,
    ): ConfigurationScopeContext {
        if ($scope !== ConfigurationScope::GLOBAL) {
            if ($tenantId === null) {
                throw ValidationException::withMessages(['tenant_id' => ['Select a tenant.']]);
            }
            $writable
                ? $this->targets->assertTenantWritable($tenantId)
                : $this->targets->assertTenantReadable($tenantId);
        }

        return $this->scopes->explicit(
            $scope,
            $tenantId,
            $organizationUnitId,
            $writable,
        );
    }

    private function definitionForMutation(
        ConfigurationScopeContext $context,
        string $key,
        bool $platform,
    ): ConfigurationDefinition {
        $canManage = $platform
            ? $this->authorization->canManagePlatformScope($context->scope)
            : $this->authorization->canManageScopeCurrent($context->scope);
        if (! $canManage) {
            throw new AuthorizationException('Managing configuration in this scope is not authorized.');
        }

        $definition = $this->definitions->get(strtolower(trim($key)));
        if (! $definition->runtimeMutable) {
            throw new AuthorizationException('This setting is not runtime mutable.');
        }
        if (! in_array($context->scope, $definition->allowedScopes, true)) {
            throw ValidationException::withMessages([
                'scope' => ['The selected scope is not allowed for this setting.'],
            ]);
        }
        $canManageSensitive = $platform
            ? $this->authorization->canManagePlatformSensitive()
            : $this->authorization->canManageSensitiveCurrent($context->scope);
        if ($definition->sensitive && ! $canManageSensitive) {
            throw new AuthorizationException('Managing sensitive configuration values is not authorized.');
        }

        return $definition;
    }

    private function assertCanView(string $scope, bool $platform): void
    {
        $canView = $platform
            ? $this->authorization->canViewPlatformScope($scope)
            : $this->authorization->canViewScopeCurrent($scope);
        if (! $canView) {
            throw new AuthorizationException('Viewing configuration is not authorized.');
        }
    }

    private function view(StoredConfigurationValue $stored): ConfigurationEntryView
    {
        $definition = $this->definitions->get($stored->key);
        if (
            $stored->definitionVersion !== $definition->version
            || $stored->valueType !== $definition->valueType
            || $stored->sensitive !== $definition->sensitive
        ) {
            throw new \RuntimeException(
                "Stored configuration metadata for [{$definition->key}] does not match definition version {$definition->version}.",
            );
        }

        $context = new ConfigurationScopeContext(
            scope: $stored->scope,
            tenantId: $stored->tenantId,
            organizationUnitId: $stored->organizationUnitId,
        );
        $inherited = $this->resolver->resolveBelow($context, $definition->key);

        return new ConfigurationEntryView(
            definition: $definition,
            scope: $stored->scope,
            tenantId: $stored->tenantId,
            organizationUnitId: $stored->organizationUnitId,
            value: $definition->sensitive
                ? null
                : $this->codec->decode($definition, $stored->storedValue),
            inheritedValue: $definition->sensitive ? null : $inherited->value,
            inheritedConfigured: ! $inherited->usesDefault || $inherited->value !== null,
            inheritedSourceScope: $inherited->sourceScope,
            inheritedUsesDefault: $inherited->usesDefault,
            rowVersion: $stored->rowVersion,
            updatedAt: $stored->updatedAt,
        );
    }

    /** @return list<string> */
    private function matchingDefinitionKeys(
        string $scope,
        ?string $search,
        ?string $owner,
    ): array {
        $search = strtolower(trim((string) $search));
        $owner = strtolower(trim((string) $owner));

        return array_values(array_map(
            static fn (ConfigurationDefinition $definition): string => $definition->key,
            array_filter(
                $this->definitions->all(),
                static function (ConfigurationDefinition $definition) use ($scope, $search, $owner): bool {
                    if (! in_array($scope, $definition->allowedScopes, true)) {
                        return false;
                    }
                    if ($owner !== '' && strtolower($definition->owner) !== $owner) {
                        return false;
                    }
                    if ($search === '') {
                        return true;
                    }

                    return str_contains(strtolower($definition->key), $search)
                        || str_contains(strtolower($definition->label), $search)
                        || str_contains(strtolower($definition->description), $search);
                },
            ),
        ));
    }

    private function recordChange(
        string $action,
        ConfigurationDefinition $definition,
        StoredConfigurationValue $stored,
        mixed $before,
        mixed $after,
    ): void {
        $changes = $definition->sensitive
            ? ['value_changed' => $before !== $after]
            : ['before' => $before, 'after' => $after];
        $subjectParts = array_filter(
            [$stored->scope, $stored->tenantId, $stored->organizationUnitId, $definition->key],
            static fn (mixed $part): bool => $part !== null && $part !== '',
        );

        $event = new AuditEventData(
            eventName: 'configuration.entry.'.$action,
            eventCategory: AuditEventCategory::CONFIGURATION,
            sourceModule: 'configuration',
            subjectType: 'configuration_entry',
            subjectId: implode(':', $subjectParts),
            subjectReference: $definition->label,
            changes: $changes,
            metadata: [
                'key' => $definition->key,
                'scope' => $stored->scope,
                'tenant_id' => $stored->tenantId,
                'organization_unit_id' => $stored->organizationUnitId,
                'owner' => $definition->owner,
                'sensitive' => $definition->sensitive,
                'row_version' => $stored->rowVersion,
            ],
            tags: ['configuration', $this->ownerTag($definition->owner)],
        );

        if ($stored->scope === ConfigurationScope::GLOBAL) {
            $this->audit->recordPlatform($event);

            return;
        }

        // Platform operators can administer tenant/organization overrides without a
        // tenant request context. Preserve tenant ownership in the audit payload by
        // recording a platform event with explicit target metadata.
        if ($this->currentTenant->currentTenantId() === null) {
            $this->audit->recordPlatform($event);

            return;
        }

        $this->audit->record($event);
    }

    private function revisionView(
        ConfigurationDefinition $definition,
        ConfigurationValueRevision $revision,
    ): ConfigurationRevisionView {
        $storedValue = $revision->getAttribute('stored_value');
        $storedValue = $storedValue === null ? null : (string) $storedValue;
        $actor = $revision->relationLoaded('actor') ? $revision->getRelation('actor') : null;
        $actorName = null;
        if ($actor !== null) {
            $actorName = trim((string) $actor->getAttribute('first_name').' '.(string) $actor->getAttribute('last_name'));
            if ($actorName === '') {
                $actorName = (string) ($actor->getAttribute('platform_login_email') ?? $actor->getAttribute('email'));
            }
        }
        $createdAt = $revision->getAttribute('created_at');
        $createdAt = $createdAt instanceof DateTimeInterface
            ? DateTimeImmutable::createFromInterface($createdAt)
            : new DateTimeImmutable((string) $createdAt);

        $definitionCompatible = (int) $revision->getAttribute('definition_version') === $definition->version
            && (string) $revision->getAttribute('value_type') === $definition->valueType
            && (bool) $revision->getAttribute('is_sensitive') === $definition->sensitive;

        return new ConfigurationRevisionView(
            id: (int) $revision->getKey(),
            operation: (string) $revision->getAttribute('operation'),
            scope: $revision->scopeName(),
            tenantId: $revision->tenantId(),
            organizationUnitId: $revision->organizationUnitId(),
            key: (string) $revision->getAttribute('key'),
            definitionVersion: (int) $revision->getAttribute('definition_version'),
            definitionCompatible: $definitionCompatible,
            value: $definition->sensitive || $storedValue === null || ! $definitionCompatible
                ? null
                : $this->codec->decode($definition, $storedValue),
            configured: $storedValue !== null,
            sensitive: $definition->sensitive,
            resultingRowVersion: is_numeric($revision->getAttribute('resulting_row_version')) ? (int) $revision->getAttribute('resulting_row_version') : null,
            sourceRevisionId: is_numeric($revision->getAttribute('source_revision_id')) ? (int) $revision->getAttribute('source_revision_id') : null,
            actorUserId: is_numeric($revision->getAttribute('actor_user_id')) ? (int) $revision->getAttribute('actor_user_id') : null,
            actorName: $actorName,
            reason: $revision->getAttribute('reason') !== null ? (string) $revision->getAttribute('reason') : null,
            createdAt: $createdAt,
        );
    }

    private function ownerTag(string $owner): string
    {
        $normalized = preg_replace('/[^a-z0-9]+/', '-', strtolower(trim($owner)));
        $tag = trim((string) $normalized, '-');

        return $tag !== '' ? $tag : 'owner';
    }

    private function isUniqueConstraintViolation(QueryException $exception): bool
    {
        $sqlState = (string) ($exception->errorInfo[0] ?? $exception->getCode());
        $message = strtolower($exception->getMessage());

        return $sqlState === '23505'
            || ($sqlState === '23000'
                && (str_contains($message, 'unique') || str_contains($message, 'duplicate')));
    }
}
