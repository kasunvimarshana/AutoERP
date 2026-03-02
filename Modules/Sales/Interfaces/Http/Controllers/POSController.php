<?php
declare(strict_types=1);
namespace Modules\Sales\Interfaces\Http\Controllers;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Modules\Sales\Application\Commands\CreateSaleCommand;
use Modules\Sales\Application\Handlers\CreateSaleHandler;
use Modules\Sales\Infrastructure\Models\CashRegister;
class POSController extends Controller {
    public function __construct(
        private readonly CreateSaleHandler $createHandler,
    ) {}
    public function createTransaction(Request $request): JsonResponse {
        $validated = $request->validate([
            'cash_register_id' => 'required|integer|exists:cash_registers,id',
            'customer_id'      => 'nullable|integer',
            'lines'            => 'required|array|min:1',
            'lines.*.product_id'  => 'required|integer|exists:products,id',
            'lines.*.quantity'    => 'required|numeric|min:0.0001',
            'lines.*.unit_price'  => 'required|numeric|min:0',
            'payment_method'   => 'required|string',
            'amount_tendered'  => 'required|numeric|min:0',
            'discount_percent' => 'nullable|numeric|min:0|max:100',
        ]);
        try {
            $sale = $this->createHandler->handle(new CreateSaleCommand(
                tenantId: (int)$request->attributes->get('tenant_id'),
                organisationId: (int)($request->user()?->organisation_id ?? 1),
                customerId: isset($validated['customer_id']) ? (int)$validated['customer_id'] : null,
                lines: $validated['lines'],
                discountPercent: (string)($validated['discount_percent'] ?? '0'),
                paymentMethod: $validated['payment_method'],
                cashRegisterId: (int)$validated['cash_register_id'],
                createdBy: $request->user()?->id,
            ));
            $total   = $sale->getTotal();
            $change  = bcsub((string)$validated['amount_tendered'], $total, 4);
            return response()->json(['success'=>true,'message'=>'POS transaction completed.','data'=>[
                'sale_id'        => $sale->getId(),
                'invoice_number' => $sale->getInvoiceNumber(),
                'total'          => $total,
                'amount_tendered'=> $validated['amount_tendered'],
                'change'         => $change,
            ],'errors'=>null], 201);
        } catch (\Exception $e) {
            return response()->json(['success'=>false,'message'=>$e->getMessage(),'data'=>null,'errors'=>null], 422);
        }
    }
    public function registers(Request $request): JsonResponse {
        $tenantId = (int)$request->attributes->get('tenant_id');
        $registers = CashRegister::withoutGlobalScope('tenant')->where('tenant_id', $tenantId)->get();
        return response()->json(['success'=>true,'message'=>'Cash registers retrieved.','data'=>$registers,'errors'=>null]);
    }
    public function openRegister(Request $request): JsonResponse {
        $validated = $request->validate([
            'register_id'     => 'required|integer|exists:cash_registers,id',
            'opening_balance' => 'required|numeric|min:0',
        ]);
        $register = CashRegister::withoutGlobalScope('tenant')
            ->where('id', $validated['register_id'])
            ->where('tenant_id', (int)$request->attributes->get('tenant_id'))
            ->firstOrFail();
        $register->update([
            'is_open'         => true,
            'opening_balance' => bcadd((string)$validated['opening_balance'], '0', 4),
            'current_balance' => bcadd((string)$validated['opening_balance'], '0', 4),
            'opened_at'       => now(),
            'opened_by'       => $request->user()?->id,
            'closed_at'       => null,
        ]);
        return response()->json(['success'=>true,'message'=>'Cash register opened.','data'=>$register->fresh(),'errors'=>null]);
    }
    public function closeRegister(Request $request): JsonResponse {
        $validated = $request->validate([
            'register_id'    => 'required|integer|exists:cash_registers,id',
            'closing_balance'=> 'required|numeric|min:0',
        ]);
        $register = CashRegister::withoutGlobalScope('tenant')
            ->where('id', $validated['register_id'])
            ->where('tenant_id', (int)$request->attributes->get('tenant_id'))
            ->firstOrFail();
        $register->update([
            'is_open'    => false,
            'closed_at'  => now(),
        ]);
        return response()->json(['success'=>true,'message'=>'Cash register closed.','data'=>$register->fresh(),'errors'=>null]);
    }
}
