<?php
namespace Modules\GS1\Infrastructure\Persistence\Eloquent\Repositories;

use Illuminate\Pagination\LengthAwarePaginator;
use Modules\Core\Infrastructure\Persistence\Repositories\EloquentRepository;
use Modules\GS1\Domain\Entities\GS1Barcode;
use Modules\GS1\Domain\RepositoryInterfaces\GS1BarcodeRepositoryInterface;
use Modules\GS1\Infrastructure\Persistence\Eloquent\Models\GS1BarcodeModel;

class EloquentGS1BarcodeRepository extends EloquentRepository implements GS1BarcodeRepositoryInterface
{
    public function __construct(GS1BarcodeModel $model)
    {
        parent::__construct($model);
    }

    public function findById(int $id): ?GS1Barcode
    {
        $model = parent::findById($id);
        return $model ? $this->toEntity($model) : null;
    }

    public function findByGtin(string $gtin): ?GS1Barcode
    {
        $model = $this->model->where('gtin', $gtin)->first();
        return $model ? $this->toEntity($model) : null;
    }

    public function findByProduct(int $productId): array
    {
        return $this->model->where('product_id', $productId)
            ->get()
            ->map(fn ($m) => $this->toEntity($m))
            ->all();
    }

    public function findAll(int $tenantId, array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = $this->model->where('tenant_id', $tenantId);
        $this->applyFilters($query, $filters);
        return $query->paginate($perPage);
    }

    public function create(array $data): GS1Barcode
    {
        $model = parent::create($data);
        return $this->toEntity($model);
    }

    public function update(GS1Barcode $barcode, array $data): GS1Barcode
    {
        $model   = $this->model->findOrFail($barcode->id);
        $updated = parent::update($model, $data);
        return $this->toEntity($updated);
    }

    private function toEntity(object $model): GS1Barcode
    {
        return new GS1Barcode(
            id:               $model->id,
            tenantId:         $model->tenant_id,
            productId:        $model->product_id,
            gs1CompanyPrefix: $model->gs1_company_prefix,
            itemReference:    $model->item_reference,
            checkDigit:       $model->check_digit,
            gtin:             $model->gtin,
            barcodeType:      $model->barcode_type,
            variantId:        $model->variant_id,
            isActive:         (bool) $model->is_active,
        );
    }
}
