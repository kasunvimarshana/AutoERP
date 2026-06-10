<?php

declare(strict_types=1);

namespace Modules\Payment\Services;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Modules\Payment\Models\ChequeTemplate;

final class ChequeTemplateService
{
    /**
     * @return Collection<int, ChequeTemplate>
     */
    public function list(int $tenantId, ?int $organizationUnitId, ?bool $isActive = null): Collection
    {
        $query = $this->scope(ChequeTemplate::query(), $tenantId, $organizationUnitId);
        if ($isActive !== null) {
            $query->where('is_active', $isActive);
        }

        return $query
            ->orderByDesc('is_default')
            ->orderBy('bank_name')
            ->orderBy('template_name')
            ->get();
    }

    public function find(int $id, int $tenantId, ?int $organizationUnitId): ChequeTemplate
    {
        return $this->scope(ChequeTemplate::query(), $tenantId, $organizationUnitId)->findOrFail($id);
    }

    public function create(array $payload, int $tenantId, ?int $organizationUnitId): ChequeTemplate
    {
        return DB::transaction(function () use ($payload, $tenantId, $organizationUnitId): ChequeTemplate {
            $attributes = $this->attributes($payload);
            $attributes['tenant_id'] = $tenantId;
            $attributes['organization_unit_id'] = $organizationUnitId;
            $attributes['is_default'] = (bool) ($attributes['is_default'] ?? false);
            $attributes['is_active'] = (bool) ($attributes['is_active'] ?? true);

            if ($attributes['is_default']) {
                $this->clearDefault($tenantId, $organizationUnitId);
            }

            return ChequeTemplate::query()->create($attributes);
        });
    }

    public function update(
        ChequeTemplate $template,
        array $payload,
    ): ChequeTemplate {
        return DB::transaction(function () use ($template, $payload): ChequeTemplate {
            $attributes = $this->attributes($payload);
            if (array_key_exists('is_default', $attributes)) {
                $attributes['is_default'] = (bool) $attributes['is_default'];
            }
            if (array_key_exists('is_active', $attributes)) {
                $attributes['is_active'] = (bool) $attributes['is_active'];
            }
            if (($attributes['is_default'] ?? false) === true) {
                $this->clearDefault(
                    (int) $template->tenant_id,
                    $template->organization_unit_id,
                    (int) $template->getKey(),
                );
            }

            $template->fill($attributes)->save();

            return $template->refresh();
        });
    }

    public function delete(ChequeTemplate $template): void
    {
        if ($template->printLogs()->exists()) {
            throw new InvalidArgumentException('Templates with cheque print history cannot be deleted; deactivate the template instead.');
        }

        $template->delete();
    }

    public function resolveActive(
        int $tenantId,
        ?int $organizationUnitId,
        ?int $templateId = null,
    ): ChequeTemplate {
        $query = $this->scope(ChequeTemplate::query(), $tenantId, $organizationUnitId);

        if ($templateId !== null) {
            return $query->findOrFail($templateId);
        }

        $template = $query->where('is_active', true)->orderByDesc('is_default')->orderBy('id')->first();
        if (! $template instanceof ChequeTemplate) {
            throw new InvalidArgumentException('An active cheque template is required.');
        }

        return $template;
    }

    private function scope(Builder $query, int $tenantId, ?int $organizationUnitId): Builder
    {
        $query->where('tenant_id', $tenantId);

        if ($organizationUnitId === null) {
            return $query->whereNull('organization_unit_id');
        }

        return $query->where(function (Builder $scope) use ($organizationUnitId): void {
            $scope->whereNull('organization_unit_id')
                ->orWhere('organization_unit_id', $organizationUnitId);
        });
    }

    private function clearDefault(int $tenantId, ?int $organizationUnitId, ?int $exceptId = null): void
    {
        $query = ChequeTemplate::query()
            ->where('tenant_id', $tenantId)
            ->where('organization_unit_id', $organizationUnitId);
        if ($exceptId !== null) {
            $query->whereKeyNot($exceptId);
        }
        $query->update(['is_default' => false]);
    }

    private function attributes(array $payload): array
    {
        unset($payload['tenant_id'], $payload['organization_unit_id']);

        return $payload;
    }
}
