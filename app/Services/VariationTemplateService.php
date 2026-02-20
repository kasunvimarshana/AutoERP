<?php

namespace App\Services;

use App\Enums\AuditAction;
use App\Models\VariationTemplate;
use App\Models\VariationValueTemplate;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class VariationTemplateService
{
    public function __construct(
        private readonly AuditService $auditService
    ) {}

    public function paginate(string $tenantId, int $perPage = 50): LengthAwarePaginator
    {
        return VariationTemplate::where('tenant_id', $tenantId)
            ->with(['values'])
            ->orderBy('name')
            ->paginate($perPage);
    }

    public function create(array $data): VariationTemplate
    {
        return DB::transaction(function () use ($data) {
            $template = VariationTemplate::create([
                'tenant_id' => $data['tenant_id'],
                'name' => $data['name'],
            ]);

            foreach ($data['values'] ?? [] as $value) {
                VariationValueTemplate::create([
                    'variation_template_id' => $template->id,
                    'name' => $value,
                ]);
            }

            $this->auditService->log(
                action: AuditAction::Created,
                auditableType: VariationTemplate::class,
                auditableId: $template->id,
                newValues: $data
            );

            return $template->fresh(['values']);
        });
    }

    public function update(string $id, array $data): VariationTemplate
    {
        return DB::transaction(function () use ($id, $data) {
            $template = VariationTemplate::findOrFail($id);
            $oldValues = $template->toArray();

            if (isset($data['name'])) {
                $template->update(['name' => $data['name']]);
            }

            // Sync values if provided
            if (isset($data['values'])) {
                $template->values()->delete();
                foreach ($data['values'] as $value) {
                    VariationValueTemplate::create([
                        'variation_template_id' => $template->id,
                        'name' => $value,
                    ]);
                }
            }

            $this->auditService->log(
                action: AuditAction::Updated,
                auditableType: VariationTemplate::class,
                auditableId: $template->id,
                oldValues: $oldValues,
                newValues: $data
            );

            return $template->fresh(['values']);
        });
    }

    public function delete(string $id): void
    {
        $template = VariationTemplate::findOrFail($id);

        $this->auditService->log(
            action: AuditAction::Deleted,
            auditableType: VariationTemplate::class,
            auditableId: $template->id,
            oldValues: $template->toArray()
        );

        $template->values()->delete();
        $template->delete();
    }
}
