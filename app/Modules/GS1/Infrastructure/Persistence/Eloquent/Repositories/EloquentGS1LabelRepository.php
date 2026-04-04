<?php
namespace Modules\GS1\Infrastructure\Persistence\Eloquent\Repositories;

use Modules\Core\Infrastructure\Persistence\Repositories\EloquentRepository;
use Modules\GS1\Domain\Entities\GS1Label;
use Modules\GS1\Domain\RepositoryInterfaces\GS1LabelRepositoryInterface;
use Modules\GS1\Infrastructure\Persistence\Eloquent\Models\GS1LabelModel;

class EloquentGS1LabelRepository extends EloquentRepository implements GS1LabelRepositoryInterface
{
    public function __construct(GS1LabelModel $model)
    {
        parent::__construct($model);
    }

    public function findById(int $id): ?GS1Label
    {
        $model = parent::findById($id);
        return $model ? $this->toEntity($model) : null;
    }

    public function findByBarcode(int $barcodeId): array
    {
        return $this->model->where('barcode_id', $barcodeId)
            ->get()
            ->map(fn ($m) => $this->toEntity($m))
            ->all();
    }

    public function create(array $data): GS1Label
    {
        $model = parent::create($data);
        return $this->toEntity($model);
    }

    private function toEntity(object $model): GS1Label
    {
        return new GS1Label(
            id:           $model->id,
            tenantId:     $model->tenant_id,
            barcodeId:    $model->barcode_id,
            labelFormat:  $model->label_format,
            content:      $model->content,
            batchId:      $model->batch_id,
            serialNumber: $model->serial_number,
            generatedAt:  $model->generated_at
                ? new \DateTimeImmutable((string) $model->generated_at)
                : null,
        );
    }
}
