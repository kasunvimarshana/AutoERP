<?php

declare(strict_types=1);

namespace Modules\Extension\Services;

use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;
use LogicException;
use Modules\Core\Models\TenantOwnedModel;

final class ExtensionEntityReferenceValidator
{
    public function normalizeAndAssertExists(string $type, int $id): string
    {
        $normalizedType = strtolower(trim($type));
        if ($normalizedType === '' || $id < 1) {
            throw new InvalidArgumentException('A valid entity type and identifier are required.');
        }

        $modelClass = $this->modelClass($normalizedType);
        if (! is_subclass_of($modelClass, TenantOwnedModel::class)) {
            throw new LogicException(
                "Extension entity type [{$normalizedType}] must reference a tenant-owned model.",
            );
        }

        /** @var Model $model */
        $model = new $modelClass();
        if (! $model->newQuery()->whereKey($id)->exists()) {
            throw new InvalidArgumentException('The referenced entity does not exist in the active tenant.');
        }

        return $normalizedType;
    }

    /** @return list<string> */
    public function allowedTypes(): array
    {
        $types = config('extension.entity_types', []);

        return is_array($types)
            ? array_values(array_filter(array_map('strval', array_keys($types))))
            : [];
    }

    /** @return class-string<Model> */
    private function modelClass(string $type): string
    {
        $types = config('extension.entity_types', []);
        $modelClass = is_array($types) ? ($types[$type] ?? null) : null;

        if (! is_string($modelClass) || $modelClass === '' || ! class_exists($modelClass)) {
            throw new InvalidArgumentException('The requested entity type is not supported.');
        }

        if (! is_subclass_of($modelClass, Model::class)) {
            throw new LogicException("Extension entity type [{$type}] is not configured with an Eloquent model.");
        }

        /** @var class-string<Model> $modelClass */
        return $modelClass;
    }
}
