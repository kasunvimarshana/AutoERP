<?php

namespace App\Modules\CRMManagement\Services;

use App\Core\Base\BaseService;
use App\Modules\CRMManagement\Events\NotificationSent;
use App\Modules\CRMManagement\Events\NotificationScheduled;
use App\Modules\CRMManagement\Repositories\NotificationRepository;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class NotificationService extends BaseService
{
    public function __construct(NotificationRepository $repository)
    {
        parent::__construct($repository);
    }

    /**
     * Send notification immediately
     */
    public function send(array $data): Model
    {
        try {
            DB::beginTransaction();

            $data['status'] = 'sent';
            $data['sent_at'] = now();
            
            $notification = $this->create($data);

            event(new NotificationSent($notification));

            DB::commit();

            return $notification;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Schedule notification for future delivery
     */
    public function schedule(array $data, \DateTime $scheduledAt): Model
    {
        try {
            DB::beginTransaction();

            $data['status'] = 'scheduled';
            $data['scheduled_at'] = $scheduledAt;
            
            $notification = $this->create($data);

            event(new NotificationScheduled($notification));

            DB::commit();

            return $notification;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Send scheduled notifications that are due
     */
    public function sendScheduled(): int
    {
        $notifications = $this->repository->getScheduledDue();
        $count = 0;

        foreach ($notifications as $notification) {
            try {
                $this->update($notification->id, [
                    'status' => 'sent',
                    'sent_at' => now()
                ]);
                
                event(new NotificationSent($notification));
                $count++;
            } catch (\Exception $e) {
                // Log error but continue with other notifications
                continue;
            }
        }

        return $count;
    }

    /**
     * Get notifications by customer
     */
    public function getByCustomer(int $customerId)
    {
        return $this->repository->getByCustomer($customerId);
    }

    /**
     * Get unread notifications
     */
    public function getUnread(int $customerId)
    {
        return $this->repository->getUnread($customerId);
    }

    /**
     * Mark as read
     */
    public function markAsRead(int $notificationId): Model
    {
        return $this->update($notificationId, [
            'is_read' => true,
            'read_at' => now()
        ]);
    }

    /**
     * Mark all as read for a customer
     */
    public function markAllAsRead(int $customerId): void
    {
        $notifications = $this->repository->getUnread($customerId);
        
        foreach ($notifications as $notification) {
            $this->markAsRead($notification->id);
        }
    }

    /**
     * Cancel scheduled notification
     */
    public function cancel(int $notificationId): Model
    {
        return $this->update($notificationId, [
            'status' => 'cancelled',
            'cancelled_at' => now()
        ]);
    }

    /**
     * Get notifications by type
     */
    public function getByType(string $type)
    {
        return $this->repository->getByType($type);
    }
}
