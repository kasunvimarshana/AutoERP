<?php
namespace Modules\Accounting\Application\Services;

use Illuminate\Contracts\Events\Dispatcher;
use Modules\Accounting\Application\Contracts\ProcessRefundServiceInterface;
use Modules\Accounting\Application\DTOs\RefundData;
use Modules\Accounting\Domain\Entities\Refund;
use Modules\Accounting\Domain\Events\RefundProcessed;
use Modules\Accounting\Domain\Repositories\JournalEntryRepositoryInterface;
use Modules\Accounting\Domain\Repositories\PaymentRepositoryInterface;
use Modules\Accounting\Domain\Repositories\RefundRepositoryInterface;
use Modules\Accounting\Domain\ValueObjects\JournalEntryStatus;
use Modules\Accounting\Domain\ValueObjects\PaymentStatus;

class ProcessRefundService implements ProcessRefundServiceInterface
{
    public function __construct(
        private readonly RefundRepositoryInterface $refundRepository,
        private readonly PaymentRepositoryInterface $paymentRepository,
        private readonly JournalEntryRepositoryInterface $journalEntryRepository,
        private readonly Dispatcher $dispatcher,
    ) {}

    public function execute(RefundData $data): Refund
    {
        $payment = $this->paymentRepository->findById($data->paymentId);

        if ($payment === null) {
            throw new \DomainException("Payment [{$data->paymentId}] not found.");
        }

        if ($payment->status !== PaymentStatus::COMPLETED) {
            throw new \DomainException("Payment [{$data->paymentId}] must be completed before a refund can be processed.");
        }

        $refund = $this->refundRepository->create([
            'tenant_id'    => $data->tenantId,
            'payment_id'   => $data->paymentId,
            'amount'       => $data->amount,
            'currency'     => $data->currency,
            'status'       => 'pending',
            'reason'       => $data->reason,
            'processed_by' => $data->processedBy,
        ]);

        $journalEntryId = null;

        if ($payment->journalEntryId !== null) {
            $originalEntry = $this->journalEntryRepository->findById($payment->journalEntryId);

            if ($originalEntry !== null && $originalEntry->status === JournalEntryStatus::POSTED) {
                $originalLines = $this->journalEntryRepository->findLines($originalEntry->id);

                $reversalEntry = $this->journalEntryRepository->create([
                    'tenant_id'        => $data->tenantId,
                    'reference_number' => 'RFND-' . $originalEntry->referenceNumber,
                    'status'           => JournalEntryStatus::POSTED,
                    'entry_date'       => now()->toDateString(),
                    'description'      => 'Refund reversal for payment #' . $payment->referenceNumber,
                    'source_type'      => 'refund',
                    'source_id'        => $refund->id,
                    'posted_by'        => $data->processedBy,
                    'posted_at'        => now()->toDateTimeString(),
                ]);

                foreach ($originalLines as $line) {
                    $this->journalEntryRepository->addLine($reversalEntry->id, [
                        'journal_entry_id' => $reversalEntry->id,
                        'account_id'       => $line->accountId,
                        'debit'            => $line->credit,
                        'credit'           => $line->debit,
                        'currency'         => $line->currency,
                        'description'      => $line->description,
                    ]);
                }

                $journalEntryId = $reversalEntry->id;
            }
        }

        $refund = $this->refundRepository->update($refund, [
            'status'           => 'completed',
            'processed_by'     => $data->processedBy,
            'processed_at'     => now()->toDateTimeString(),
            'journal_entry_id' => $journalEntryId,
        ]);

        $this->dispatcher->dispatch(new RefundProcessed($data->tenantId, $refund->id));

        return $refund;
    }
}
