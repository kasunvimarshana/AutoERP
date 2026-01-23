<?php

namespace App\Modules\CRMManagement\Services;

use App\Core\Base\BaseService;
use App\Modules\CRMManagement\Events\CommunicationSent;
use App\Modules\CRMManagement\Repositories\CommunicationRepository;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class CommunicationService extends BaseService
{
    public function __construct(CommunicationRepository $repository)
    {
        parent::__construct($repository);
    }

    /**
     * Send communication
     */
    public function send(array $data): Model
    {
        try {
            DB::beginTransaction();

            $data['status'] = 'sent';
            $data['sent_at'] = now();
            
            $communication = $this->create($data);

            event(new CommunicationSent($communication));

            DB::commit();

            return $communication;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Send email
     */
    public function sendEmail(int $customerId, string $subject, string $body, array $attachments = []): Model
    {
        return $this->send([
            'customer_id' => $customerId,
            'type' => 'email',
            'subject' => $subject,
            'content' => $body,
            'attachments' => $attachments
        ]);
    }

    /**
     * Send SMS
     */
    public function sendSMS(int $customerId, string $message): Model
    {
        return $this->send([
            'customer_id' => $customerId,
            'type' => 'sms',
            'content' => $message
        ]);
    }

    /**
     * Send WhatsApp message
     */
    public function sendWhatsApp(int $customerId, string $message): Model
    {
        return $this->send([
            'customer_id' => $customerId,
            'type' => 'whatsapp',
            'content' => $message
        ]);
    }

    /**
     * Get communications by customer
     */
    public function getByCustomer(int $customerId)
    {
        return $this->repository->getByCustomer($customerId);
    }

    /**
     * Get communications by type
     */
    public function getByType(string $type)
    {
        return $this->repository->getByType($type);
    }

    /**
     * Get communications by date range
     */
    public function getByDateRange(\DateTime $startDate, \DateTime $endDate)
    {
        return $this->repository->getByDateRange($startDate, $endDate);
    }

    /**
     * Mark as read
     */
    public function markAsRead(int $communicationId): Model
    {
        return $this->update($communicationId, [
            'is_read' => true,
            'read_at' => now()
        ]);
    }

    /**
     * Mark as failed
     */
    public function markAsFailed(int $communicationId, string $reason): Model
    {
        return $this->update($communicationId, [
            'status' => 'failed',
            'failure_reason' => $reason,
            'failed_at' => now()
        ]);
    }
}
