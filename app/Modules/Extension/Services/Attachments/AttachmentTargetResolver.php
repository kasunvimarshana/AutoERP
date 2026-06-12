<?php

declare(strict_types=1);

namespace Modules\Extension\Services\Attachments;

use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;
use Modules\Extension\DTOs\AttachmentTarget;
use Modules\OrganizationUnit\Models\OrganizationUnitModel;
use Modules\Tenant\Models\TenantModel;

final class AttachmentTargetResolver
{
    public function resolve(
        string $alias,
        int $id,
        int $tenantId,
        ?int $currentOrganizationUnitId,
    ): AttachmentTarget {
        $normalizedAlias = strtolower(trim($alias));
        $definition = config('extension.attachments.attachables.'.$normalizedAlias);

        if (! is_array($definition) || ! isset($definition['model'], $definition['module'])) {
            throw new InvalidArgumentException('Unsupported attachment target type.');
        }

        $modelClass = $definition['model'];
        if (! is_string($modelClass) || ! is_subclass_of($modelClass, Model::class)) {
            throw new InvalidArgumentException('Attachment target configuration is invalid.');
        }

        /** @var Model|null $model */
        $model = $modelClass::query()->find($id);
        if (! $model instanceof Model) {
            throw new InvalidArgumentException('Attachment target was not found.');
        }

        $targetTenantId = $this->targetTenantId($model);
        if ($targetTenantId !== $tenantId) {
            throw new InvalidArgumentException('Attachment target does not belong to the current tenant.');
        }

        $targetOrganizationUnitId = $this->targetOrganizationUnitId($model);
        if (
            $targetOrganizationUnitId !== null
            && $currentOrganizationUnitId !== null
            && $targetOrganizationUnitId !== $currentOrganizationUnitId
        ) {
            throw new InvalidArgumentException('Attachment target does not belong to the current organization unit.');
        }

        $referenceColumn = isset($definition['reference']) && is_string($definition['reference'])
            ? $definition['reference']
            : null;
        $reference = $referenceColumn === null ? null : $this->nullableString($model->getAttribute($referenceColumn));

        return new AttachmentTarget(
            alias: $normalizedAlias,
            module: (string) $definition['module'],
            model: $model,
            tenantId: $targetTenantId,
            organizationUnitId: $targetOrganizationUnitId,
            reference: $reference,
        );
    }

    private function targetTenantId(Model $model): int
    {
        if ($model instanceof TenantModel) {
            return (int) $model->getKey();
        }

        $tenantId = $model->getAttribute('tenant_id');
        if (! is_numeric($tenantId) || (int) $tenantId < 1) {
            throw new InvalidArgumentException('Attachment target has no valid tenant ownership.');
        }

        return (int) $tenantId;
    }

    private function targetOrganizationUnitId(Model $model): ?int
    {
        if ($model instanceof OrganizationUnitModel) {
            return (int) $model->getKey();
        }

        $organizationUnitId = $model->getAttribute('organization_unit_id');

        return is_numeric($organizationUnitId) && (int) $organizationUnitId > 0
            ? (int) $organizationUnitId
            : null;
    }

    private function nullableString(mixed $value): ?string
    {
        if (! is_scalar($value)) {
            return null;
        }

        $normalized = trim((string) $value);

        return $normalized === '' ? null : $normalized;
    }
}
