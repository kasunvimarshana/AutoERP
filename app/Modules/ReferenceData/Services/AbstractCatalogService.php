<?php

declare(strict_types=1);

namespace Modules\ReferenceData\Services;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\QueryException;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Modules\Audit\Constants\AuditEventCategory;
use Modules\Audit\Contracts\AuditRecorderInterface;
use Modules\Audit\Data\AuditEventData;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

abstract class AbstractCatalogService
{
    public function __construct(private readonly AuditRecorderInterface $audit) {}

    /** @return class-string<Model> */
    abstract protected function modelClass(): string;

    abstract protected function resourceName(): string;

    /** @param array<string, mixed> $data @return array<string, mixed> */
    abstract protected function normalizeCreate(array $data): array;

    /** @param array<string, mixed> $data @return array<string, mixed> */
    abstract protected function normalizeUpdate(array $data): array;

    /** @return LengthAwarePaginator<int, Model> */
    public function list(
        ?string $search,
        ?bool $isActive,
        int $page,
        int $perPage,
    ): LengthAwarePaginator {
        $query = $this->query();
        $search = trim((string) $search);

        if ($search !== '') {
            $escaped = str_replace(['!', '%', '_'], ['!!', '!%', '!_'], $search);

            $query->where(function (Builder $builder) use ($escaped): void {
                foreach ($this->searchColumns() as $index => $column) {
                    $builder->whereRaw(
                        sprintf("%s LIKE ? ESCAPE '!'", $column),
                        ['%'.$escaped.'%'],
                        $index === 0 ? 'and' : 'or',
                    );
                }
            });
        }

        if ($isActive !== null) {
            $query->where('is_active', $isActive);
        }

        return $query
            ->orderBy($this->orderColumn())
            ->orderBy('id')
            ->paginate(
                min(max($perPage, 1), 1000),
                ['*'],
                'page',
                max($page, 1),
            );
    }

    public function find(int $id): Model
    {
        $model = $this->query()->find($id);

        return $model instanceof Model
            ? $model
            : throw new NotFoundHttpException(ucfirst($this->resourceName()).' was not found.');
    }

    /** @param array<string, mixed> $data */
    public function create(array $data): Model
    {
        return DB::transaction(function () use ($data): Model {
            $class = $this->modelClass();
            $model = new $class();
            $model->fill($this->normalizeCreate($data));
            $model->setAttribute('row_version', 1);

            try {
                $model->save();
            } catch (QueryException $exception) {
                if (! $this->isUniqueConstraintViolation($exception)) {
                    throw $exception;
                }

                throw new ConflictHttpException(
                    ucfirst($this->resourceName()).' identifier already exists.',
                    previous: $exception,
                );
            }

            $model->refresh();
            $this->record('created', $model, null, $this->publicSnapshot($model));

            return $model;
        }, 3);
    }

    /** @param array<string, mixed> $data */
    public function update(
        int $id,
        int $expectedVersion,
        array $data,
    ): Model {
        return DB::transaction(function () use ($id, $expectedVersion, $data): Model {
            $current = $this->find($id);

            if ((int) $current->getAttribute('row_version') !== $expectedVersion) {
                throw new ConflictHttpException(
                    'This record changed after it was loaded. Refresh and try again.',
                );
            }

            $attributes = $this->normalizeUpdate($data);
            if ($attributes === []) {
                throw ValidationException::withMessages([
                    'record' => ['Provide at least one editable field to update.'],
                ]);
            }

            $changes = array_filter(
                $attributes,
                static fn (mixed $value, string $attribute): bool =>
                    $current->getAttribute($attribute) !== $value,
                ARRAY_FILTER_USE_BOTH,
            );

            if ($changes === []) {
                return $current;
            }

            $before = $this->publicSnapshot($current);
            $changes['row_version'] = $expectedVersion + 1;
            $changes['updated_at'] = now();

            $updated = $this->query()
                ->whereKey($id)
                ->where('row_version', $expectedVersion)
                ->update($changes);

            if ($updated !== 1) {
                throw new ConflictHttpException(
                    'This record changed after it was loaded. Refresh and try again.',
                );
            }

            $model = $this->find($id);
            $this->record('updated', $model, $before, $this->publicSnapshot($model));

            return $model;
        }, 3);
    }

    public function setActive(
        int $id,
        int $expectedVersion,
        bool $isActive,
    ): Model {
        return DB::transaction(function () use ($id, $expectedVersion, $isActive): Model {
            $current = $this->find($id);

            if ((int) $current->getAttribute('row_version') !== $expectedVersion) {
                throw new ConflictHttpException(
                    'This record changed after it was loaded. Refresh and try again.',
                );
            }

            if ((bool) $current->getAttribute('is_active') === $isActive) {
                return $current;
            }

            $this->assertStatusChangeAllowed($current, $isActive);
            $before = $this->publicSnapshot($current);

            $updated = $this->query()
                ->whereKey($id)
                ->where('row_version', $expectedVersion)
                ->update([
                    'is_active' => $isActive,
                    'row_version' => $expectedVersion + 1,
                    'updated_at' => now(),
                ]);

            if ($updated !== 1) {
                throw new ConflictHttpException(
                    'This record changed after it was loaded. Refresh and try again.',
                );
            }

            $model = $this->find($id);
            $this->record(
                $isActive ? 'activated' : 'deactivated',
                $model,
                $before,
                $this->publicSnapshot($model),
            );

            return $model;
        }, 3);
    }

    /** @return list<string> */
    protected function searchColumns(): array
    {
        return ['code', 'name'];
    }

    protected function orderColumn(): string
    {
        return 'name';
    }

    protected function assertStatusChangeAllowed(Model $model, bool $isActive): void {}

    /** @return Builder<Model> */
    private function query(): Builder
    {
        $class = $this->modelClass();

        return $class::query();
    }

    /** @return array<string, mixed> */
    private function publicSnapshot(Model $model): array
    {
        return collect($model->attributesToArray())
            ->except(['created_at', 'updated_at'])
            ->all();
    }

    /** @param array<string, mixed>|null $before @param array<string, mixed>|null $after */
    private function record(
        string $action,
        Model $model,
        ?array $before,
        ?array $after,
    ): void {
        $this->audit->record(new AuditEventData(
            eventName: 'reference_data.'.$this->resourceName().'.'.$action,
            eventCategory: AuditEventCategory::CONFIGURATION,
            sourceModule: 'reference_data',
            subjectType: $this->resourceName(),
            subjectId: (string) $model->getKey(),
            subjectReference: (string) (
                $model->getAttribute('code')
                ?? $model->getAttribute('name')
                ?? $model->getKey()
            ),
            changes: ['before' => $before, 'after' => $after],
            tags: ['reference-data', $this->resourceName()],
        ));
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
