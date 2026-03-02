<?php
declare(strict_types=1);
namespace Modules\Sales\Application\Handlers;
use Illuminate\Support\Facades\DB;
use Modules\Sales\Application\Commands\CreateSaleCommand;
use Modules\Sales\Domain\Contracts\SaleRepositoryInterface;
use Modules\Sales\Domain\Entities\Sale;
use Modules\Sales\Domain\Enums\PaymentStatus;
use Modules\Sales\Domain\Enums\SaleStatus;
use Modules\Sales\Domain\ValueObjects\SaleTotal;
use Modules\Sales\Infrastructure\Models\Sale as SaleModel;
use Modules\Sales\Infrastructure\Models\SaleLine as SaleLineModel;
class CreateSaleHandler {
    public function __construct(
        private readonly SaleRepositoryInterface $sales,
    ) {}
    public function handle(CreateSaleCommand $command): Sale {
        return DB::transaction(function () use ($command): Sale {
            // Calculate line totals
            $processedLines = [];
            foreach ($command->lines as $line) {
                $qty      = bcadd((string)$line['quantity'], '0', 4);
                $price    = bcadd((string)$line['unit_price'], '0', 4);
                $disc     = bcadd((string)($line['discount_percent'] ?? '0'), '0', 4);
                $tax      = bcadd((string)($line['tax_percent'] ?? '0'), '0', 4);
                $gross    = bcmul($qty, $price, 4);
                $discAmt  = bcdiv(bcmul($gross, $disc, 4), '100', 4);
                $net      = bcsub($gross, $discAmt, 4);
                $taxAmt   = bcdiv(bcmul($net, $tax, 4), '100', 4);
                $lineTotal = bcadd($net, $taxAmt, 4);
                $processedLines[] = array_merge($line, ['line_total' => $lineTotal]);
            }
            // Calculate sale totals
            $totals = SaleTotal::calculate($processedLines, $command->discountPercent, $command->taxPercent);
            // Generate invoice number
            $invoiceNumber = $this->sales->generateInvoiceNumber($command->tenantId, $command->organisationId);
            // Persist sale
            $saleModel = SaleModel::create([
                'tenant_id'       => $command->tenantId,
                'organisation_id' => $command->organisationId,
                'invoice_number'  => $invoiceNumber,
                'customer_id'     => $command->customerId,
                'status'          => SaleStatus::CONFIRMED->value,
                'payment_status'  => PaymentStatus::PENDING->value,
                'subtotal'        => $totals->subtotal,
                'discount_amount' => $totals->discountAmount,
                'tax_amount'      => $totals->taxAmount,
                'total'           => $totals->total,
                'paid_amount'     => '0.0000',
                'due_amount'      => $totals->total,
                'sale_date'       => $command->saleDate ?? now()->toDateString(),
                'notes'           => $command->notes,
                'created_by'      => $command->createdBy,
                'cash_register_id' => $command->cashRegisterId,
            ]);
            // Persist sale lines
            foreach ($processedLines as $line) {
                SaleLineModel::create([
                    'tenant_id'       => $command->tenantId,
                    'sale_id'         => $saleModel->id,
                    'product_id'      => $line['product_id'],
                    'variant_id'      => $line['variant_id'] ?? null,
                    'quantity'        => $line['quantity'],
                    'unit_price'      => $line['unit_price'],
                    'discount_percent'=> $line['discount_percent'] ?? '0',
                    'tax_percent'     => $line['tax_percent'] ?? '0',
                    'line_total'      => $line['line_total'],
                    'notes'           => $line['notes'] ?? null,
                ]);
            }
            return $this->sales->findById((int)$saleModel->id, $command->tenantId);
        });
    }
}
