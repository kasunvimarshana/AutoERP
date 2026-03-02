<?php
declare(strict_types=1);
namespace Modules\Procurement\Interfaces\Http\Controllers;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Procurement\Application\Commands\CreatePurchaseOrderCommand;
use Modules\Procurement\Application\Handlers\CreatePurchaseOrderHandler;
use Modules\Procurement\Domain\Contracts\PurchaseRepositoryInterface;
class PurchaseController extends Controller {
    public function __construct(
        private readonly CreatePurchaseOrderHandler  $createHandler,
        private readonly PurchaseRepositoryInterface $purchases,
    ) {}
    public function index(Request $request): JsonResponse {
        $tenantId = (int)$request->attributes->get('tenant_id');
        $orders = \Modules\Procurement\Infrastructure\Models\PurchaseOrder::withoutGlobalScope('tenant')
            ->where('tenant_id', $tenantId)
            ->with('vendor')
            ->orderByDesc('created_at')
            ->paginate((int)$request->query('per_page', 25));
        return response()->json(['success'=>true,'message'=>'Purchase orders retrieved.','data'=>$orders,'errors'=>null]);
    }
    public function show(Request $request, int $id): JsonResponse {
        $tenantId = (int)$request->attributes->get('tenant_id');
        $po = $this->purchases->findById($id, $tenantId);
        if (!$po) return response()->json(['success'=>false,'message'=>'Purchase order not found.','data'=>null,'errors'=>null], 404);
        return response()->json(['success'=>true,'message'=>'Purchase order retrieved.','data'=>[
            'id'=>$po->getId(),'po_number'=>$po->getPoNumber(),'status'=>$po->getStatus()->value,
            'total'=>$po->getTotal(),'vendor_id'=>$po->getVendorId(),
        ],'errors'=>null]);
    }
    public function store(Request $request): JsonResponse {
        $validated = $request->validate([
            'vendor_id'              => 'required|integer|exists:vendors,id',
            'lines'                  => 'required|array|min:1',
            'lines.*.product_id'     => 'required|integer|exists:products,id',
            'lines.*.quantity'       => 'required|numeric|min:0.0001',
            'lines.*.unit_cost'      => 'required|numeric|min:0',
            'lines.*.tax_percent'    => 'nullable|numeric|min:0',
            'expected_delivery_date' => 'nullable|date',
            'notes'                  => 'nullable|string',
        ]);
        try {
            $po = $this->createHandler->handle(new CreatePurchaseOrderCommand(
                tenantId: (int)$request->attributes->get('tenant_id'),
                vendorId: (int)$validated['vendor_id'],
                lines: $validated['lines'],
                expectedDeliveryDate: $validated['expected_delivery_date'] ?? null,
                notes: $validated['notes'] ?? null,
                createdBy: $request->user()?->id,
            ));
            return response()->json(['success'=>true,'message'=>'Purchase order created.','data'=>['id'=>$po->getId(),'po_number'=>$po->getPoNumber(),'total'=>$po->getTotal()],'errors'=>null], 201);
        } catch (\Exception $e) {
            return response()->json(['success'=>false,'message'=>$e->getMessage(),'data'=>null,'errors'=>null], 422);
        }
    }
    public function update(Request $request, int $id): JsonResponse {
        $tenantId = (int)$request->attributes->get('tenant_id');
        $po = \Modules\Procurement\Infrastructure\Models\PurchaseOrder::withoutGlobalScope('tenant')->where('id',$id)->where('tenant_id',$tenantId)->first();
        if (!$po) return response()->json(['success'=>false,'message'=>'Purchase order not found.','data'=>null,'errors'=>null], 404);
        $po->update($request->only(['status','notes','expected_delivery_date']));
        return response()->json(['success'=>true,'message'=>'Purchase order updated.','data'=>$po->fresh(),'errors'=>null]);
    }
    public function destroy(Request $request, int $id): JsonResponse {
        $tenantId = (int)$request->attributes->get('tenant_id');
        $this->purchases->delete($id, $tenantId);
        return response()->json(['success'=>true,'message'=>'Purchase order deleted.','data'=>null,'errors'=>null]);
    }
    public function receive(Request $request, int $id): JsonResponse {
        $tenantId = (int)$request->attributes->get('tenant_id');
        $po = $this->purchases->findById($id, $tenantId);
        if (!$po) return response()->json(['success'=>false,'message'=>'Purchase order not found.','data'=>null,'errors'=>null], 404);
        if (!$po->getStatus()->canReceiveGoods()) {
            return response()->json(['success'=>false,'message'=>'Purchase order is not in a receivable state.','data'=>null,'errors'=>null], 422);
        }
        return response()->json(['success'=>true,'message'=>'Goods receipt recorded (stub — implement with InventoryModule integration).','data'=>['po_id'=>$id],'errors'=>null]);
    }
}
