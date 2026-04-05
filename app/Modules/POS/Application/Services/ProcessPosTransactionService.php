<?php
declare(strict_types=1);
namespace Modules\POS\Application\Services;

use Modules\POS\Domain\Entities\PosTransaction;
use Modules\POS\Domain\Exceptions\PosSessionNotFoundException;
use Modules\POS\Domain\RepositoryInterfaces\PosSessionRepositoryInterface;
use Modules\POS\Domain\RepositoryInterfaces\PosTransactionRepositoryInterface;

class ProcessPosTransactionService
{
    public function __construct(
        private readonly PosSessionRepositoryInterface $sessionRepository,
        private readonly PosTransactionRepositoryInterface $transactionRepository,
    ) {}

    /**
     * Process a POS sale or refund transaction.
     *
     * $data keys: session_id, customer_id, type, currency, payment_method,
     *             amount_tendered, notes, lines[]
     * Each line: product_id, variant_id, product_name, sku, quantity,
     *            unit_price, discount_amount, tax_amount
     */
    public function process(int $tenantId, array $data): PosTransaction
    {
        $session = $this->sessionRepository->findById($data['session_id']);
        if ($session === null) {
            throw new PosSessionNotFoundException($data['session_id']);
        }
        if (!$session->isOpen()) {
            throw new \DomainException("Cannot process transaction on a closed session.");
        }

        $lines   = $data['lines'] ?? [];
        $subtotal = 0.0;
        $taxTotal = 0.0;
        $discountTotal = 0.0;

        foreach ($lines as &$line) {
            $lineTotal = ($line['quantity'] * $line['unit_price'])
                - ($line['discount_amount'] ?? 0.0)
                + ($line['tax_amount'] ?? 0.0);
            $line['line_total']  = round($lineTotal, 6);
            $subtotal           += $line['quantity'] * $line['unit_price'];
            $taxTotal           += $line['tax_amount'] ?? 0.0;
            $discountTotal      += $line['discount_amount'] ?? 0.0;
        }
        unset($line);

        $total = $subtotal - $discountTotal + $taxTotal;
        $amountTendered = $data['amount_tendered'] ?? $total;
        $changeGiven = max(0.0, $amountTendered - $total);

        $transactionData = [
            'tenant_id'       => $tenantId,
            'session_id'      => $data['session_id'],
            'customer_id'     => $data['customer_id'] ?? null,
            'type'            => $data['type'] ?? PosTransaction::TYPE_SALE,
            'status'          => PosTransaction::STATUS_COMPLETED,
            'currency'        => $data['currency'] ?? 'USD',
            'subtotal'        => round($subtotal, 6),
            'tax_total'       => round($taxTotal, 6),
            'discount_total'  => round($discountTotal, 6),
            'total'           => round($total, 6),
            'payment_method'  => $data['payment_method'] ?? 'cash',
            'amount_tendered' => round($amountTendered, 6),
            'change_given'    => round($changeGiven, 6),
            'reference'       => $data['reference'] ?? null,
            'notes'           => $data['notes'] ?? null,
        ];

        return $this->transactionRepository->create($transactionData, $lines);
    }
}
