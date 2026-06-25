<?php

declare(strict_types=1);

namespace Modules\Audit\Repositories;

use DateTimeImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;
use Modules\Audit\Data\AuditCursorPage;
use Modules\Audit\Data\AuditQueryData;
use Modules\Audit\Data\AuditReadScope;
use Modules\Audit\Models\AuditLog;
use Modules\Core\DTOs\DataRecord;

final class EloquentAuditLogReader implements AuditLogReaderInterface
{
    public function __construct(private readonly AuditLog $model) {}

    public function findVisibleById(AuditReadScope $scope, int $id): ?DataRecord
    {
        $model = $this->applyScope($this->model->newQuery(), $scope)->whereKey($id)->first();

        return $model instanceof Model ? $this->toRecord($model) : null;
    }

    public function cursorPage(AuditReadScope $scope, AuditQueryData $query): AuditCursorPage
    {
        $builder = $this->applyFilters($this->applyScope($this->model->newQuery(), $scope), $query)
            ->orderByDesc('occurred_at')
            ->orderByDesc('id');

        if ($query->cursor !== null) {
            [$occurredAt, $id] = $this->decodeCursor($query->cursor);
            $builder->where(function (Builder $cursorQuery) use ($occurredAt, $id): void {
                $cursorQuery
                    ->where('occurred_at', '<', $occurredAt->format('Y-m-d H:i:s'))
                    ->orWhere(function (Builder $sameTime) use ($occurredAt, $id): void {
                        $sameTime
                            ->where('occurred_at', '=', $occurredAt->format('Y-m-d H:i:s'))
                            ->where('id', '<', $id);
                    });
            });
        }

        $models = $builder->limit($query->perPage + 1)->get();
        $hasMore = $models->count() > $query->perPage;
        $models = $models->take($query->perPage)->values();

        $items = $models
            ->filter(static fn (mixed $model): bool => $model instanceof Model)
            ->map(fn (Model $model): DataRecord => $this->toRecord($model))
            ->values()
            ->all();

        $nextCursor = null;
        if ($hasMore && $models->isNotEmpty()) {
            /** @var Model $last */
            $last = $models->last();
            $nextCursor = $this->encodeCursor(
                new DateTimeImmutable((string) $last->getAttribute('occurred_at')),
                (int) $last->getKey(),
            );
        }

        return new AuditCursorPage($items, $nextCursor, $query->perPage);
    }

    private function applyScope(Builder $query, AuditReadScope $scope): Builder
    {
        if ($scope->platformWide) {
            if ($scope->tenantId !== null) {
                $query->where('tenant_id', $scope->tenantId);
            }

            return $query;
        }

        if ($scope->tenantId === null) {
            throw new InvalidArgumentException('Tenant audit scope requires a tenant identifier.');
        }

        $query->where('tenant_id', $scope->tenantId);

        if (! $scope->tenantWide) {
            $query->where('organization_unit_id', $scope->organizationUnitId);
        }

        return $query;
    }

    private function applyFilters(Builder $query, AuditQueryData $filters): Builder
    {
        $this->whereExact($query, 'event_category', $filters->eventCategory);
        $this->whereExact($query, 'event_name', $filters->eventName);
        $this->whereExact($query, 'source_module', $filters->sourceModule);
        $this->whereExact($query, 'actor_type', $filters->actorType);
        $this->whereExact($query, 'actor_id', $filters->actorId);
        $this->whereExact($query, 'subject_type', $filters->subjectType);
        $this->whereExact($query, 'subject_id', $filters->subjectId);

        if ($filters->fromUtc !== null) {
            $query->where('occurred_at', '>=', $filters->fromUtc->format('Y-m-d H:i:s'));
        }

        if ($filters->toUtcExclusive !== null) {
            $query->where('occurred_at', '<', $filters->toUtcExclusive->format('Y-m-d H:i:s'));
        }

        return $query;
    }

    private function whereExact(Builder $query, string $column, ?string $value): void
    {
        $value = $value !== null ? trim($value) : null;
        if ($value !== null && $value !== '') {
            $query->where($column, $value);
        }
    }

    /** @return array{DateTimeImmutable, int} */
    private function decodeCursor(string $cursor): array
    {
        $encoded = strtr($cursor, '-_', '+/');
        $padding = strlen($encoded) % 4;
        if ($padding !== 0) {
            $encoded .= str_repeat('=', 4 - $padding);
        }

        $decoded = base64_decode($encoded, true);
        $payload = $decoded !== false ? json_decode($decoded, true) : null;

        if (! is_array($payload) || ! is_string($payload['occurred_at'] ?? null) || ! is_numeric($payload['id'] ?? null)) {
            throw new InvalidArgumentException('Invalid audit cursor.');
        }

        return [new DateTimeImmutable($payload['occurred_at']), (int) $payload['id']];
    }

    private function encodeCursor(DateTimeImmutable $occurredAt, int $id): string
    {
        $json = json_encode([
            'occurred_at' => $occurredAt->format(DATE_ATOM),
            'id' => $id,
        ], JSON_THROW_ON_ERROR);

        return rtrim(strtr(base64_encode($json), '+/', '-_'), '=');
    }

    private function toRecord(Model $model): DataRecord
    {
        /** @var array<string, mixed> $attributes */
        $attributes = $model->attributesToArray();

        return new DataRecord($attributes);
    }
}
