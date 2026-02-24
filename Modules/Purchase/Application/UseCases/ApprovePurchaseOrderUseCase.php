<?php
namespace Modules\Purchase\Application\UseCases;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Modules\Purchase\Domain\Contracts\PurchaseOrderRepositoryInterface;
use Modules\Purchase\Domain\Events\PurchaseOrderApproved;
class ApprovePurchaseOrderUseCase
{
    public function __construct(private PurchaseOrderRepositoryInterface $repo) {}
    public function execute(string $poId): object
    {
        return DB::transaction(function () use ($poId) {
            $po = $this->repo->findById($poId);
            if (!$po) throw new \RuntimeException('Purchase order not found.');
            if ($po->status !== 'draft') throw new \RuntimeException('Only draft orders can be approved.');
            $updated = $this->repo->update($poId, [
                'status' => 'approved',
                'approved_at' => now(),
                'approved_by' => auth()->id(),
            ]);
            Event::dispatch(new PurchaseOrderApproved($poId));
            return $updated;
        });
    }
}
