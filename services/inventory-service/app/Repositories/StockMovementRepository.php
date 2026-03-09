<?php
namespace App\Repositories;
use App\Domain\Contracts\StockMovementRepositoryInterface;
use App\Domain\Models\StockMovement;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class StockMovementRepository extends BaseRepository implements StockMovementRepositoryInterface
{
    protected array $searchableFields = ['reference_id','notes'];
    public function __construct(StockMovement $model) { parent::__construct($model); }
    protected function getAllowedFilterFields(): array { return ['tenant_id','product_id','warehouse_id','type','reference_type','performed_by']; }
    protected function getAllowedSortFields(): array { return ['performed_at','created_at','type','quantity']; }
    protected function getAllowedRelations(): array { return ['product','warehouse']; }

    public function findByIdAndTenant(string $id, string $tenantId): object
    {
        $m = $this->model->byTenant($tenantId)->with(['product','warehouse'])->find($id);
        if (!$m) throw new ModelNotFoundException("StockMovement {$id} not found.");
        return $m;
    }

    public function getByTenant(string $tenantId, array $params = []): mixed
    { $params['filter']['tenant_id'] = $tenantId; return $this->query($params); }

    public function getMovementsByProduct(string $tenantId, string $productId, array $params = []): mixed
    { $params['filter']['tenant_id'] = $tenantId; $params['filter']['product_id'] = $productId; return $this->query($params); }

    public function getMovementsByWarehouse(string $tenantId, string $warehouseId, array $params = []): mixed
    { $params['filter']['tenant_id'] = $tenantId; $params['filter']['warehouse_id'] = $warehouseId; return $this->query($params); }

    public function getMovementsByReference(string $tenantId, string $referenceId, string $referenceType): mixed
    {
        return $this->model->byTenant($tenantId)->where('reference_id',$referenceId)->where('reference_type',$referenceType)->with(['product','warehouse'])->get();
    }
}
