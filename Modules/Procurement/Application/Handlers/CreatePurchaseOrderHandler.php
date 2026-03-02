<?php
declare(strict_types=1);
namespace Modules\Procurement\Application\Handlers;
use Illuminate\Support\Facades\DB;
use Modules\Procurement\Application\Commands\CreatePurchaseOrderCommand;
use Modules\Procurement\Domain\Contracts\PurchaseRepositoryInterface;
use Modules\Procurement\Domain\Entities\PurchaseOrder;
use Modules\Procurement\Domain\Enums\PurchaseStatus;
use Modules\Procurement\Infrastructure\Models\PurchaseOrder as POModel;
use Modules\Procurement\Infrastructure\Models\PurchaseOrderLine as POLineModel;
class CreatePurchaseOrderHandler {
    public function __construct(
        private readonly PurchaseRepositoryInterface $purchases,
    ) {}
    public function handle(CreatePurchaseOrderCommand $command): PurchaseOrder {
        return DB::transaction(function () use ($command): PurchaseOrder {
            $poNumber = $this->purchases->generatePoNumber($command->tenantId);
            $subtotal = '0.0000';
            $taxTotal = '0.0000';
            $processedLines = [];
            foreach ($command->lines as $line) {
                $qty      = bcadd((string)$line['quantity'], '0', 4);
                $cost     = bcadd((string)$line['unit_cost'], '0', 4);
                $taxPct   = bcadd((string)($line['tax_percent'] ?? '0'), '0', 4);
                $net      = bcmul($qty, $cost, 4);
                $tax      = bcdiv(bcmul($net, $taxPct, 4), '100', 4);
                $lineTotal = bcadd($net, $tax, 4);
                $subtotal  = bcadd($subtotal, $net, 4);
                $taxTotal  = bcadd($taxTotal, $tax, 4);
                $processedLines[] = array_merge($line, ['line_total' => $lineTotal, 'qty' => $qty, 'cost' => $cost, 'tax_pct' => $taxPct]);
            }
            $total = bcadd($subtotal, $taxTotal, 4);
            $poModel = POModel::create([
                'tenant_id'             => $command->tenantId,
                'vendor_id'             => $command->vendorId,
                'po_number'             => $poNumber,
                'status'                => PurchaseStatus::DRAFT->value,
                'subtotal'              => $subtotal,
                'tax_amount'            => $taxTotal,
                'total'                 => $total,
                'expected_delivery_date'=> $command->expectedDeliveryDate,
                'notes'                 => $command->notes,
                'created_by'            => $command->createdBy,
            ]);
            foreach ($processedLines as $line) {
                POLineModel::create([
                    'tenant_id'        => $command->tenantId,
                    'purchase_order_id'=> $poModel->id,
                    'product_id'       => $line['product_id'],
                    'variant_id'       => $line['variant_id'] ?? null,
                    'quantity'         => $line['qty'],
                    'unit_cost'        => $line['cost'],
                    'tax_percent'      => $line['tax_pct'],
                    'line_total'       => $line['line_total'],
                    'received_quantity'=> '0.0000',
                    'notes'            => $line['notes'] ?? null,
                ]);
            }
            return $this->purchases->findById((int)$poModel->id, $command->tenantId);
        });
    }
}
