<?php

declare(strict_types=1);

namespace Modules\Barcode\Infrastructure\Persistence\Eloquent\Repositories;

use Modules\Barcode\Domain\Entities\LabelTemplate;
use Modules\Barcode\Domain\RepositoryInterfaces\LabelTemplateRepositoryInterface;
use Modules\Barcode\Infrastructure\Persistence\Eloquent\Models\LabelTemplateModel;

class EloquentLabelTemplateRepository implements LabelTemplateRepositoryInterface
{
    public function __construct(private readonly LabelTemplateModel $model) {}

    private function hydrate(LabelTemplateModel $m): LabelTemplate
    {
        return new LabelTemplate(
            $m->id,
            $m->tenant_id,
            $m->name,
            $m->format,
            $m->content,
            $m->default_variables ?? [],
            (bool) $m->is_active,
            $m->created_at,
            $m->updated_at,
        );
    }

    private function persist(LabelTemplate $template): LabelTemplateModel
    {
        $data = [
            'tenant_id'          => $template->getTenantId(),
            'name'               => $template->getName(),
            'format'             => $template->getFormat(),
            'content'            => $template->getContent(),
            'default_variables'  => $template->getDefaultVariables(),
            'is_active'          => $template->isActive(),
        ];

        if ($template->getId() === null) {
            return $this->model->newQuery()->create($data);
        }

        $m = $this->model->newQuery()->findOrFail($template->getId());
        $m->update($data);

        return $m->fresh();
    }

    public function findById(int $id): ?LabelTemplate
    {
        $m = $this->model->newQuery()->find($id);
        return $m ? $this->hydrate($m) : null;
    }

    /** @return LabelTemplate[] */
    public function findAll(int $tenantId): array
    {
        return $this->model->newQuery()
            ->where('tenant_id', $tenantId)
            ->get()
            ->map(fn($m) => $this->hydrate($m))
            ->all();
    }

    /** @return LabelTemplate[] */
    public function findActive(int $tenantId): array
    {
        return $this->model->newQuery()
            ->where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->get()
            ->map(fn($m) => $this->hydrate($m))
            ->all();
    }

    /** @return LabelTemplate[] */
    public function findByFormat(int $tenantId, string $format): array
    {
        return $this->model->newQuery()
            ->where('tenant_id', $tenantId)
            ->where('format', $format)
            ->get()
            ->map(fn($m) => $this->hydrate($m))
            ->all();
    }

    public function save(LabelTemplate $template): LabelTemplate
    {
        return $this->hydrate($this->persist($template));
    }

    public function delete(int $id): void
    {
        $this->model->newQuery()->where('id', $id)->delete();
    }
}
