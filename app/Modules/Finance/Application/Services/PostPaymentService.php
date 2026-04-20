<?php

declare(strict_types=1);

namespace Modules\Finance\Application\Services;

use Modules\Core\Application\Services\BaseService;
use Modules\Core\Domain\Exceptions\DomainException;
use Modules\Finance\Application\Contracts\PostPaymentServiceInterface;
use Modules\Finance\Domain\Entities\Payment;
use Modules\Finance\Domain\Exceptions\PaymentNotFoundException;
use Modules\Finance\Domain\RepositoryInterfaces\PaymentRepositoryInterface;

class PostPaymentService extends BaseService implements PostPaymentServiceInterface
{
    public function __construct(private readonly PaymentRepositoryInterface $paymentRepository)
    {
        parent::__construct($paymentRepository);
    }

    protected function handle(array $data): Payment
    {
        $id = (int) ($data['id'] ?? 0);

        $payment = $this->paymentRepository->find($id);
        if (! $payment) {
            throw new PaymentNotFoundException($id);
        }

        if (! $payment->isDraft()) {
            throw new DomainException('Only draft payments can be posted.');
        }

        $journalEntryId = isset($data['journal_entry_id']) ? (int) $data['journal_entry_id'] : null;

        $payment->post($journalEntryId);

        return $this->paymentRepository->save($payment);
    }
}
