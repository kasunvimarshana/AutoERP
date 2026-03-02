<?php
declare(strict_types=1);
namespace Modules\Sales\Interfaces\Http\Controllers;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Sales\Application\Commands\CreateSaleCommand;
use Modules\Sales\Application\Handlers\CreateSaleHandler;
use Modules\Sales\Domain\Contracts\SaleRepositoryInterface;
use Modules\Sales\Interfaces\Http\Resources\SaleResource;
class SaleController extends Controller {
    public function __construct(
        private readonly CreateSaleHandler      $createHandler,
        private readonly SaleRepositoryInterface $sales,
    ) {}
    public function index(Request $request): JsonResponse {
        $tenantId = (int)$request->attributes->get('tenant_id');
        $page     = (int)$request->query('page', 1);
        $perPage  = min((int)$request->query('per_page', 25), 100);
        $items    = $this->sales->findAll($tenantId, $page, $perPage);
        return response()->json(['success'=>true,'message'=>'Sales retrieved.','data'=>array_map(fn($s)=>[
            'id'=>$s->getId(),'invoice_number'=>$s->getInvoiceNumber(),
            'total'=>$s->getTotal(),'status'=>$s->getSaleStatus()->value,
            'payment_status'=>$s->getPaymentStatus()->value,
        ], $items),'errors'=>null]);
    }
    public function show(Request $request, int $id): JsonResponse {
        $tenantId = (int)$request->attributes->get('tenant_id');
        $sale = $this->sales->findById($id, $tenantId);
        if (!$sale) return response()->json(['success'=>false,'message'=>'Sale not found.','data'=>null,'errors'=>null], 404);
        return response()->json(['success'=>true,'message'=>'Sale retrieved.','data'=>[
            'id'=>$sale->getId(),'invoice_number'=>$sale->getInvoiceNumber(),
            'total'=>$sale->getTotal(),'due_amount'=>$sale->calculateDue(),
            'status'=>$sale->getSaleStatus()->value,'payment_status'=>$sale->getPaymentStatus()->value,
        ],'errors'=>null]);
    }
    public function store(Request $request): JsonResponse {
        $validated = $request->validate([
            'customer_id'      => 'nullable|integer',
            'lines'            => 'required|array|min:1',
            'lines.*.product_id'   => 'required|integer|exists:products,id',
            'lines.*.quantity'     => 'required|numeric|min:0.0001',
            'lines.*.unit_price'   => 'required|numeric|min:0',
            'lines.*.discount_percent' => 'nullable|numeric|min:0|max:100',
            'lines.*.tax_percent'  => 'nullable|numeric|min:0',
            'discount_percent' => 'nullable|numeric|min:0|max:100',
            'tax_percent'      => 'nullable|numeric|min:0',
            'notes'            => 'nullable|string',
            'sale_date'        => 'nullable|date',
        ]);
        try {
            $sale = $this->createHandler->handle(new CreateSaleCommand(
                tenantId: (int)$request->attributes->get('tenant_id'),
                organisationId: (int)($request->user()?->organisation_id ?? 1),
                customerId: isset($validated['customer_id']) ? (int)$validated['customer_id'] : null,
                lines: $validated['lines'],
                discountPercent: (string)($validated['discount_percent'] ?? '0'),
                taxPercent: (string)($validated['tax_percent'] ?? '0'),
                notes: $validated['notes'] ?? null,
                saleDate: $validated['sale_date'] ?? null,
                createdBy: $request->user()?->id,
            ));
            return response()->json(['success'=>true,'message'=>'Sale created.','data'=>['id'=>$sale->getId(),'invoice_number'=>$sale->getInvoiceNumber(),'total'=>$sale->getTotal()],'errors'=>null], 201);
        } catch (\Exception $e) {
            return response()->json(['success'=>false,'message'=>$e->getMessage(),'data'=>null,'errors'=>null], 422);
        }
    }
    public function update(Request $request, int $id): JsonResponse {
        return response()->json(['success'=>false,'message'=>'Use specific status/payment endpoints.','data'=>null,'errors'=>null], 405);
    }
    public function destroy(Request $request, int $id): JsonResponse {
        $tenantId = (int)$request->attributes->get('tenant_id');
        $this->sales->delete($id, $tenantId);
        return response()->json(['success'=>true,'message'=>'Sale deleted.','data'=>null,'errors'=>null]);
    }
}
