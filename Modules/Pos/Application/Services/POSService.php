<?php

declare(strict_types=1);

namespace Modules\POS\Application\Services;

use Illuminate\Support\Facades\DB;
use Modules\Core\Application\Helpers\DecimalHelper;
use Modules\Core\Domain\Contracts\ServiceContract;
use Modules\POS\Application\DTOs\CreatePOSTransactionDTO;
use Modules\POS\Domain\Contracts\POSRepositoryContract;
use Modules\POS\Domain\Entities\PosSession;
use Modules\POS\Domain\Entities\PosTransaction;

/**
 * POS service.
 *
 * Orchestrates POS transaction creation, voiding, and offline sync.
 * All arithmetic uses DecimalHelper (BCMath) — no float allowed.
 */
class POSService implements ServiceContract
{
    public function __construct(
        private readonly POSRepositoryContract $posRepository,
    ) {}

    /**
     * Create a new POS transaction with lines and payments.
     *
     * Calculates line totals, subtotal, total, paid amount, and change due using BCMath.
     * Wrapped in a DB transaction for atomicity.
     */
    public function createTransaction(CreatePOSTransactionDTO $dto): PosTransaction
    {
        return DB::transaction(function () use ($dto): PosTransaction {
            $subtotal  = '0';
            $lineData  = [];

            foreach ($dto->lines as $line) {
                $quantity       = (string) $line['quantity'];
                $unitPrice      = (string) $line['unit_price'];
                $discountAmount = (string) $line['discount_amount'];

                // line_total = (quantity × unit_price) - discount_amount
                $gross     = DecimalHelper::mul($quantity, $unitPrice, DecimalHelper::SCALE_INTERMEDIATE);
                $lineTotal = DecimalHelper::sub($gross, $discountAmount, DecimalHelper::SCALE_STANDARD);

                $subtotal = DecimalHelper::add($subtotal, $lineTotal, DecimalHelper::SCALE_STANDARD);

                $lineData[] = [
                    'product_id'      => (int) $line['product_id'],
                    'uom_id'          => (int) $line['uom_id'],
                    'quantity'        => DecimalHelper::round($quantity, DecimalHelper::SCALE_STANDARD),
                    'unit_price'      => DecimalHelper::round($unitPrice, DecimalHelper::SCALE_STANDARD),
                    'discount_amount' => DecimalHelper::round($discountAmount, DecimalHelper::SCALE_STANDARD),
                    'line_total'      => DecimalHelper::round($lineTotal, DecimalHelper::SCALE_STANDARD),
                ];
            }

            // order-level discount applied after subtotal
            $totalAmount = DecimalHelper::sub($subtotal, $dto->discountAmount, DecimalHelper::SCALE_STANDARD);

            // sum payments
            $paidAmount = '0';
            foreach ($dto->payments as $payment) {
                $paidAmount = DecimalHelper::add($paidAmount, (string) $payment['amount'], DecimalHelper::SCALE_STANDARD);
            }

            // change_due = paid - total (only when paid >= total)
            $changeDue = DecimalHelper::greaterThanOrEqual($paidAmount, $totalAmount)
                ? DecimalHelper::sub($paidAmount, $totalAmount, DecimalHelper::SCALE_STANDARD)
                : '0.0000';

            /** @var PosTransaction $transaction */
            $transaction = $this->posRepository->create([
                'pos_session_id'     => $dto->sessionId,
                'transaction_number' => $this->generateTransactionNumber(),
                'status'             => 'completed',
                'subtotal'           => DecimalHelper::round($subtotal, DecimalHelper::SCALE_STANDARD),
                'discount_amount'    => DecimalHelper::round($dto->discountAmount, DecimalHelper::SCALE_STANDARD),
                'tax_amount'         => '0.0000',
                'total_amount'       => DecimalHelper::round($totalAmount, DecimalHelper::SCALE_STANDARD),
                'paid_amount'        => DecimalHelper::round($paidAmount, DecimalHelper::SCALE_STANDARD),
                'change_due'         => DecimalHelper::round($changeDue, DecimalHelper::SCALE_STANDARD),
                'is_synced'          => ! $dto->isOffline,
                'created_offline'    => $dto->isOffline,
                'completed_at'       => now(),
            ]);

            foreach ($lineData as $line) {
                $transaction->lines()->create(array_merge($line, ['tenant_id' => $transaction->tenant_id]));
            }

            foreach ($dto->payments as $payment) {
                $transaction->payments()->create([
                    'tenant_id'      => $transaction->tenant_id,
                    'payment_method' => (string) $payment['payment_method'],
                    'amount'         => DecimalHelper::round((string) $payment['amount'], DecimalHelper::SCALE_STANDARD),
                    'reference'      => $payment['reference'] ?? null,
                ]);
            }

            return $transaction->load(['lines', 'payments']);
        });
    }

    /**
     * Void a POS transaction (set status to cancelled).
     */
    public function voidTransaction(int $transactionId): PosTransaction
    {
        return DB::transaction(function () use ($transactionId): PosTransaction {
            /** @var PosTransaction $transaction */
            $transaction = $this->posRepository->findOrFail($transactionId);
            $transaction->update(['status' => 'cancelled']);

            return $transaction->fresh();
        });
    }

    /**
     * Mark offline transactions as synced.
     *
     * @param  array<int, int>  $transactionIds
     * @return array<int, int>  Synced transaction IDs
     */
    public function syncOfflineTransactions(array $transactionIds): array
    {
        return DB::transaction(function () use ($transactionIds): array {
            $synced = [];

            foreach ($transactionIds as $id) {
                $transaction = $this->posRepository->findById((int) $id);

                if ($transaction !== null && ! $transaction->is_synced) {
                    $transaction->update(['is_synced' => true]);
                    $synced[] = $transaction->id;
                }
            }

            return $synced;
        });
    }

    /**
     * Generate a unique POS transaction number: POS-{YYYYMMDD}-{suffix}.
     */
    private function generateTransactionNumber(): string
    {
        return 'POS-' . now()->format('Ymd') . '-' . strtoupper(substr(uniqid('', true), -6));
    }

    /**
     * Open a new POS session.
     *
     * @param array<string, mixed> $data
     */
    public function openSession(array $data): \Illuminate\Database\Eloquent\Model
    {
        return DB::transaction(function () use ($data): \Illuminate\Database\Eloquent\Model {
            return $this->posRepository->createSession($data);
        });
    }

    /**
     * Close a POS session.
     */
    public function closeSession(int|string $sessionId): \Illuminate\Database\Eloquent\Model
    {
        return DB::transaction(function () use ($sessionId): \Illuminate\Database\Eloquent\Model {
            /** @var PosSession $session */
            $session = PosSession::findOrFail($sessionId);
            $session->update([
                'status'    => 'closed',
                'closed_at' => now(),
            ]);

            return $session->fresh();
        });
    }

    /**
     * Show a single POS transaction by ID.
     */
    public function showTransaction(int|string $id): \Illuminate\Database\Eloquent\Model
    {
        return $this->posRepository->findOrFail($id);
    }

    /**
     * List all POS sessions.
     */
    public function listSessions(): \Illuminate\Database\Eloquent\Collection
    {
        return $this->posRepository->allSessions();
    }
}
