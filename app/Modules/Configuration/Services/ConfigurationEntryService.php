<?php

declare(strict_types=1);

namespace Modules\Configuration\Services;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\QueryException;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Modules\Audit\Constants\AuditEventCategory;
use Modules\Audit\Contracts\AuditRecorderInterface;
use Modules\Audit\Data\AuditEventData;
use Modules\Configuration\Constants\ConfigurationRevisionAction;
use Modules\Configuration\Contracts\ConfigurationDefinitionRegistryInterface;
use Modules\Configuration\Contracts\ConfigurationRevisionRepositoryInterface;
use Modules\Configuration\Contracts\ConfigurationValueRepositoryInterface;
use Modules\Configuration\Data\ConfigurationDefinition;
use Modules\Configuration\Data\ConfigurationEntryView;
use Modules\Configuration\Data\ConfigurationRevisionView;
use Modules\Configuration\Data\ConfigurationScopeContext;
use Modules\Configuration\Data\ResolvedConfigurationValue;
use Modules\Configuration\Data\StoredConfigurationValue;
use Modules\Core\Contracts\CurrentOrganizationUnitContextAccessorInterface;
use Modules\Core\Contracts\CurrentTenantContextAccessorInterface;
use Modules\Core\Contracts\CurrentUserContextAccessorInterface;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

final class ConfigurationEntryService
{
    public function __construct(
        private readonly ConfigurationDefinitionRegistryInterface $definitions,
        private readonly ConfigurationValueRepositoryInterface $repository,
        private readonly ConfigurationRevisionRepositoryInterface $revisions,
        private readonly ConfigurationScopeResolver $scopes,
        private readonly ConfigurationValueValidator $validator,
        private readonly ConfigurationValueCodec $codec,
        private readonly ResolveConfiguration $resolver,
        private readonly ConfigurationAuthorizationService $authorization,
        private readonly CurrentTenantContextAccessorInterface $currentTenant,
        private readonly CurrentOrganizationUnitContextAccessorInterface $currentOrganizationUnit,
        private readonly CurrentUserContextAccessorInterface $currentUser,
        private readonly AuditRecorderInterface $audit,
    ) {}

    /** @return LengthAwarePaginator<int, ConfigurationEntryView> */
    public function list(string $scope, ?string $prefix, int $page, int $perPage): LengthAwarePaginator
    {
        $this->assertCanView($scope);
        $context = $this->scopes->current($scope);
        $paginator = $this->repository->paginate($context, $prefix, $page, $perPage);
        $paginator->setCollection($paginator->getCollection()->map(
            fn (StoredConfigurationValue $stored): ConfigurationEntryView => $this->view($stored),
        ));

        return $paginator;
    }

    public function exact(string $scope, string $key): ConfigurationEntryView
    {
        $this->assertCanView($scope);
        $context = $this->scopes->current($scope);
        $stored = $this->resolver->exact($context, strtolower(trim($key)));

        return $stored !== null
            ? $this->view($stored)
            : throw new NotFoundHttpException(
                'The configuration override was not found in the selected scope.',
            );
    }

    /** @return LengthAwarePaginator<int, ConfigurationRevisionView> */
    public function history(
        string $scope,
        string $key,
        int $page,
        int $perPage,
    ): LengthAwarePaginator {
        $this->assertCanView($scope);
        $normalizedKey = strtolower(trim($key));
        $this->definitions->get($normalizedKey);

        return $this->revisions->paginate(
            $this->scopes->current($scope),
            $normalizedKey,
            $page,
            $perPage,
        );
    }

    public function resolvedCurrent(string $key): ResolvedConfigurationValue
    {
        if (! $this->authorization->canViewCurrent()) {
            throw new AuthorizationException('Viewing configuration is not authorized.');
        }

        return $this->resolver->resolve(
            strtolower(trim($key)),
            $this->currentTenant->requireCurrent()->tenantId(),
            $this->currentOrganizationUnit->currentOrganizationUnitId(),
        );
    }

    public function create(string $scope, string $key, mixed $value): ConfigurationEntryView
    {
        $context = $this->scopes->current($scope);
        $definition = $this->definitionForMutation($context, $key);
        $normalized = $this->validator->validate($definition, $value);

        $stored = DB::transaction(function () use (
            $context,
            $definition,
            $normalized,
        ): StoredConfigurationValue {
            if ($this->repository->findExact($context, $definition->key) !== null) {
                throw new ConflictHttpException(
                    'A configuration override already exists in the selected scope.',
                );
            }

            try {
                $created = $this->repository->create($context, [
                    'key' => $definition->key,
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

            $this->recordChange(
                ConfigurationRevisionAction::CREATED,
                $definition,
                $created,
                null,
                $normalized,
            );

            return $created;
        }, 3);

        $this->resolver->forget($context, $definition->key);

        return $this->view($stored);
    }

    public function update(
        string $scope,
        string $key,
        int $expectedVersion,
        mixed $value,
    ): ConfigurationEntryView {
        $context = $this->scopes->current($scope);
        $definition = $this->definitionForMutation($context, $key);
        $normalized = $this->validator->validate($definition, $value);

        $stored = DB::transaction(function () use (
            $context,
            $definition,
            $expectedVersion,
            $normalized,
        ): StoredConfigurationValue {
            $current = $this->repository->findExact($context, $definition->key)
                ?? throw new NotFoundHttpException(
                    'The configuration override was not found in the selected scope.',
                );

            if ($current->rowVersion !== $expectedVersion) {
                throw new ConflictHttpException(
                    'This setting changed after it was loaded. Refresh and try again.',
                );
            }

            $before = $this->codec->decode($definition, $current->storedValue);
            if ($before === $normalized) {
                return $current;
            }

            $updated = $this->repository->updateExpected($current, $expectedVersion, [
                'value' => $this->codec->encode($definition, $normalized),
                'value_type' => $definition->valueType,
                'is_sensitive' => $definition->sensitive,
            ]);
            $this->recordChange(
                ConfigurationRevisionAction::UPDATED,
                $definition,
                $updated,
                $before,
                $normalized,
            );

            return $updated;
        }, 3);

        $this->resolver->forget($context, $definition->key);

        return $this->view($stored);
    }

    public function delete(string $scope, string $key, int $expectedVersion): void
    {
        $context = $this->scopes->current($scope);
        $definition = $this->definitionForMutation($context, $key);

        DB::transaction(function () use ($context, $definition, $expectedVersion): void {
            $current = $this->repository->findExact($context, $definition->key)
                ?? throw new NotFoundHttpException(
                    'The configuration override was not found in the selected scope.',
                );
            if ($current->rowVersion !== $expectedVersion) {
                throw new ConflictHttpException(
                    'This setting changed after it was loaded. Refresh and try again.',
                );
            }

            $before = $this->codec->decode($definition, $current->storedValue);
            $this->repository->deleteExpected($current, $expectedVersion);
            $this->recordChange(
                ConfigurationRevisionAction::REMOVED,
                $definition,
                $current,
                $before,
                null,
            );
        }, 3);

        $this->resolver->forget($context, $definition->key);
    }

    private function definitionForMutation(
        ConfigurationScopeContext $context,
        string $key,
    ): ConfigurationDefinition {
        if (! $this->authorization->canManageScopeCurrent($context->scope)) {
            throw new AuthorizationException(
                'Managing configuration in this scope is not authorized.',
            );
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
        if ($definition->sensitive
            && ! $this->authorization->canManageSensitiveCurrent($context->scope)) {
            throw new AuthorizationException(
                'Managing sensitive configuration values is not authorized.',
            );
        }

        return $definition;
    }

    private function assertCanView(string $scope): void
    {
        if (! $this->authorization->canViewScopeCurrent($scope)) {
            throw new AuthorizationException('Viewing configuration is not authorized.');
        }
    }

    private function view(StoredConfigurationValue $stored): ConfigurationEntryView
    {
        $definition = $this->definitions->get($stored->key);
        if ($stored->valueType !== $definition->valueType
            || $stored->sensitive !== $definition->sensitive) {
            throw new \RuntimeException(
                "Stored configuration metadata for [{$definition->key}] does not match its definition.",
            );
        }

        return new ConfigurationEntryView(
            definition: $definition,
            scope: $stored->scope,
            tenantId: $stored->tenantId,
            organizationUnitId: $stored->organizationUnitId,
            value: $definition->sensitive
                ? null
                : $this->codec->decode($definition, $stored->storedValue),
            rowVersion: $stored->rowVersion,
            updatedAt: $stored->updatedAt,
        );
    }

    private function recordChange(
        string $action,
        ConfigurationDefinition $definition,
        StoredConfigurationValue $stored,
        mixed $before,
        mixed $after,
    ): void {
        $beforeExists = $action !== ConfigurationRevisionAction::CREATED;
        $afterExists = $action !== ConfigurationRevisionAction::REMOVED;
        $context = new ConfigurationScopeContext(
            scope: $stored->scope,
            tenantId: $stored->tenantId,
            organizationUnitId: $stored->organizationUnitId,
        );
        $currentUser = $this->currentUser->current();

        $this->revisions->record($context, [
            'key' => $definition->key,
            'action' => $action,
            'value_type' => $definition->valueType,
            'is_sensitive' => $definition->sensitive,
            'before_exists' => $beforeExists,
            'before_value' => $definition->sensitive ? null : $before,
            'after_exists' => $afterExists,
            'after_value' => $definition->sensitive ? null : $after,
            'entry_row_version' => $stored->rowVersion,
            'changed_by' => $currentUser?->userId(),
            'changed_by_name' => $currentUser === null
                ? null
                : $this->actorName($currentUser->user()),
        ]);

        $changes = $definition->sensitive
            ? ['protected_value_changed' => true]
            : [
                'before' => ['exists' => $beforeExists, 'value' => $before],
                'after' => ['exists' => $afterExists, 'value' => $after],
            ];
        $subjectParts = array_filter(
            [$stored->scope, $stored->tenantId, $stored->organizationUnitId, $definition->key],
            static fn (mixed $part): bool => $part !== null && $part !== '',
        );

        $this->audit->record(new AuditEventData(
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
        ));
    }

    private function actorName(Authenticatable $user): string
    {
        $attribute = static function (Authenticatable $user, string $key): ?string {
            if (! method_exists($user, 'getAttribute')) {
                return null;
            }

            $value = $user->getAttribute($key);

            return is_scalar($value) && trim((string) $value) !== ''
                ? trim((string) $value)
                : null;
        };

        $fullName = trim(implode(' ', array_filter([
            $attribute($user, 'first_name'),
            $attribute($user, 'last_name'),
        ])));

        return $fullName !== ''
            ? $fullName
            : ($attribute($user, 'name')
                ?? $attribute($user, 'email')
                ?? 'Authenticated operator');
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
