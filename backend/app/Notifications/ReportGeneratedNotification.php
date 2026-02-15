<?php

declare(strict_types=1);

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class ReportGeneratedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private readonly string $reportType,
        private readonly string $fileName
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        $reportTitles = [
            'sales' => 'Sales Report',
            'inventory' => 'Inventory Report',
            'financial' => 'Financial Report',
            'customer' => 'Customer Report',
            'procurement' => 'Procurement Report',
        ];

        return [
            'type' => 'report_generated',
            'report_type' => $this->reportType,
            'file_name' => $this->fileName,
            'title' => 'Report Generated',
            'message' => ($reportTitles[$this->reportType] ?? 'Report') . " has been generated and is ready for download.",
            'action_url' => "/reports/download?file={$this->fileName}",
            'severity' => 'success',
        ];
    }

    public function toArray(object $notifiable): array
    {
        return $this->toDatabase($notifiable);
    }
}
