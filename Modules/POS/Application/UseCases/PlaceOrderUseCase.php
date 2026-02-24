<?php

namespace Modules\POS\Application\UseCases;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Str;
use Modules\POS\Domain\Contracts\PosDiscountRepositoryInterface;
use Modules\POS\Domain\Contracts\PosOrderPaymentRepositoryInterface;
use Modules\POS\Domain\Contracts\PosOrderRepositoryInterface;
use Modules\POS\Domain\Contracts\PosSessionRepositoryInterface;
use Modules\POS\Domain\Contracts\PosTerminalRepositoryInterface;
use Modules\POS\Domain\Events\DiscountCodeApplied;
use Modules\POS\Domain\Events\PosOrderPlaced;
use Modules\POS\Domain\Events\SplitPaymentProcessed;
use Modules\Shared\Domain\Contracts\UseCaseInterface;

class PlaceOrderUseCase implements UseCaseInterface
{
    public function __construct(
        private PosOrderRepositoryInterface $orderRepo,
        private PosSessionRepositoryInterface $sessionRepo,
        private ?PosDiscountRepositoryInterface $discountRepo = null,
        private ?PosOrderPaymentRepositoryInterface $paymentRepo = null,
        private ?PosTerminalRepositoryInterface $terminalRepo = null,
    ) {}

    public function execute(array $data): mixed
    {
        $session = $this->sessionRepo->findById($data['session_id']);
        if (!$session) {
            throw new \DomainException('Session not found.');
        }
        if ($session->status !== 'open') {
            throw new \DomainException('Orders can only be placed in an open session.');
        }

        $tenantId = $data['tenant_id'] ?? auth()->user()?->tenant_id ?? null;
        $scale = 8;

        // Resolve the terminal's stock location (if terminal repo is available)
        $terminalLocationId = null;
        if ($this->terminalRepo !== null && ! empty($session->terminal_id)) {
            $terminal = $this->terminalRepo->findById($session->terminal_id);
            $terminalLocationId = $terminal?->location_id ?? null;
        }

        $subtotal  = '0.00000000';
        $taxTotal  = '0.00000000';
        $processedLines = [];

        foreach ($data['lines'] as $line) {
            $unitPrice = (string) $line['unit_price'];
            $quantity  = (string) $line['quantity'];
            $discount  = (string) ($line['discount'] ?? '0');
            $taxRate   = (string) ($line['tax_rate'] ?? '0');

            // discounted unit price
            $discountedPrice = bcsub(
                $unitPrice,
                bcmul($unitPrice, bcdiv($discount, '100', $scale), $scale),
                $scale
            );

            // line subtotal (before tax)
            $lineSubtotal = bcmul($discountedPrice, $quantity, $scale);

            // line tax
            $lineTax = bcmul($lineSubtotal, bcdiv($taxRate, '100', $scale), $scale);

            // line total
            $lineTotal = bcadd($lineSubtotal, $lineTax, $scale);

            $subtotal = bcadd($subtotal, $lineSubtotal, $scale);
            $taxTotal = bcadd($taxTotal, $lineTax, $scale);

            $processedLines[] = array_merge($line, [
                'id'           => (string) Str::uuid(),
                'unit_price'   => $unitPrice,
                'quantity'     => $quantity,
                'discount'     => $discount,
                'tax_rate'     => $taxRate,
                'line_total'   => $lineTotal,
            ]);
        }

        $total = bcadd($subtotal, $taxTotal, $scale);

        // --- Order-level discount code resolution ---
        $discountCodeId     = null;
        $discountAmount     = '0.00000000';
        $resolvedDiscount   = null;

        if (! empty($data['discount_code']) && $this->discountRepo !== null) {
            $resolvedDiscount = $this->discountRepo->findByCode($tenantId, $data['discount_code']);

            if (! $resolvedDiscount) {
                throw new \DomainException('Discount code not found.');
            }
            if (! $resolvedDiscount->is_active) {
                throw new \DomainException('Discount code is inactive.');
            }
            if ($resolvedDiscount->expires_at !== null &&
                strtotime((string) $resolvedDiscount->expires_at) < time()) {
                throw new \DomainException('Discount code has expired.');
            }
            if ($resolvedDiscount->usage_limit !== null &&
                $resolvedDiscount->times_used >= $resolvedDiscount->usage_limit) {
                throw new \DomainException('Discount code usage limit has been reached.');
            }

            $discountCodeId = $resolvedDiscount->id;
            $discountValue  = (string) $resolvedDiscount->value;

            if ($resolvedDiscount->type === 'percentage') {
                // percentage of pre-tax subtotal, capped at subtotal
                $computed = bcmul($subtotal, bcdiv($discountValue, '100', $scale), $scale);
                $discountAmount = bccomp($computed, $subtotal, $scale) > 0 ? $subtotal : $computed;
            } else {
                // fixed amount, capped at total (cannot produce negative total)
                $discountAmount = bccomp($discountValue, $total, $scale) > 0 ? $total : $discountValue;
            }

            $total = bcsub($total, $discountAmount, $scale);
            if (bccomp($total, '0', $scale) < 0) {
                $total = '0.00000000';
            }
        }
        // --- End discount resolution ---

        // --- Split payment / single payment resolution ---
        $splitPayments = ! empty($data['payments']) && is_array($data['payments'])
            ? $data['payments']
            : null;

        $paymentMethod = null;
        $cashTendered  = null;
        $changeAmount  = null;

        if ($splitPayments !== null) {
            // Validate: at least one payment
            if (count($splitPayments) === 0) {
                throw new \DomainException('At least one payment is required for split payment.');
            }

            // Validate: amounts sum to order total
            $paymentsSum = '0.00000000';
            foreach ($splitPayments as $p) {
                $amt = (string) ($p['amount'] ?? '0');
                if (bccomp($amt, '0', $scale) <= 0) {
                    throw new \DomainException('Each split payment amount must be greater than zero.');
                }
                $paymentsSum = bcadd($paymentsSum, $amt, $scale);
            }

            if (bccomp($paymentsSum, $total, $scale) !== 0) {
                throw new \DomainException(
                    'Split payment amounts (' . $paymentsSum . ') do not equal order total (' . $total . ').'
                );
            }

            // Determine payment method label: 'split' if multiple, else the single method
            $paymentMethod = count($splitPayments) === 1
                ? ($splitPayments[0]['payment_method'] ?? 'cash')
                : 'split';

            // Handle cash change for any cash portion in split
            $cashPortion = '0.00000000';
            foreach ($splitPayments as $p) {
                if (($p['payment_method'] ?? '') === 'cash') {
                    $cashPortion = bcadd($cashPortion, (string) ($p['amount'] ?? '0'), $scale);
                }
            }
            if (bccomp($cashPortion, '0', $scale) > 0) {
                $cashTendered = (string) ($data['cash_tendered'] ?? $cashPortion);
                $change = bcsub($cashTendered, $cashPortion, $scale);
                if (bccomp($change, '0', $scale) < 0) {
                    throw new \DomainException('Insufficient cash tendered.');
                }
                $changeAmount = $change;
            }
        } else {
            $paymentMethod = $data['payment_method'] ?? 'cash';
            if ($paymentMethod === 'cash') {
                $cashTendered = (string) ($data['cash_tendered'] ?? '0');
                $change = bcsub($cashTendered, $total, $scale);
                if (bccomp($change, '0', $scale) < 0) {
                    throw new \DomainException('Insufficient cash tendered.');
                }
                $changeAmount = $change;
            }
        }
        // --- End split payment resolution ---

        return DB::transaction(function () use (
            $data, $tenantId, $subtotal, $taxTotal, $total,
            $cashTendered, $changeAmount, $paymentMethod, $processedLines, $session, $scale,
            $discountCodeId, $discountAmount, $resolvedDiscount, $splitPayments,
            $terminalLocationId
        ) {
            $order = $this->orderRepo->create([
                'tenant_id'        => $tenantId,
                'session_id'       => $session->id,
                'number'           => $this->orderRepo->nextNumber($tenantId),
                'customer_id'      => $data['customer_id'] ?? null,
                'status'           => 'paid',
                'payment_method'   => $paymentMethod,
                'subtotal'         => $subtotal,
                'tax_total'        => $taxTotal,
                'total'            => $total,
                'cash_tendered'    => $cashTendered,
                'change_amount'    => $changeAmount,
                'currency'         => $data['currency'] ?? 'USD',
                'discount_code_id' => $discountCodeId,
                'discount_amount'  => $discountAmount,
                'created_by'       => $data['created_by'] ?? auth()->id(),
                'lines'            => $processedLines,
            ]);

            if ($resolvedDiscount !== null && $this->discountRepo !== null) {
                $this->discountRepo->incrementUsage($resolvedDiscount->id);
                Event::dispatch(new DiscountCodeApplied(
                    $order->id,
                    $tenantId,
                    $resolvedDiscount->id,
                    $resolvedDiscount->code,
                    $discountAmount,
                ));
            }

            // Record individual payment rows
            if ($this->paymentRepo !== null) {
                $paymentsToRecord = $splitPayments ?? [[
                    'payment_method' => $paymentMethod,
                    'amount'         => $total,
                    'reference'      => $data['payment_reference'] ?? null,
                ]];

                foreach ($paymentsToRecord as $p) {
                    $this->paymentRepo->create([
                        'tenant_id'      => $tenantId,
                        'order_id'       => $order->id,
                        'payment_method' => $p['payment_method'] ?? 'cash',
                        'amount'         => (string) ($p['amount'] ?? '0'),
                        'reference'      => $p['reference'] ?? null,
                    ]);
                }

                if ($splitPayments !== null && count($splitPayments) > 1) {
                    Event::dispatch(new SplitPaymentProcessed(
                        $order->id,
                        $tenantId,
                        count($splitPayments),
                        $total,
                    ));
                }
            }

            $this->sessionRepo->update($session->id, [
                'total_sales' => bcadd((string) $session->total_sales, $total, $scale),
                'order_count' => $session->order_count + 1,
            ]);

            // Build enriched line data for the Inventory listener
            $eventLines = array_map(fn ($l) => [
                'product_id'  => $l['product_id'] ?? null,
                'variant_id'  => $l['variant_id'] ?? null,
                'quantity'    => (string) ($l['quantity'] ?? '0'),
                'location_id' => $terminalLocationId,
            ], $processedLines);

            Event::dispatch(new PosOrderPlaced(
                orderId:     $order->id,
                tenantId:    (string) $tenantId,
                lines:       $eventLines,
                customerId:  $data['customer_id'] ?? null,
                totalAmount: $total,
            ));

            return $order;
        });
    }
}
