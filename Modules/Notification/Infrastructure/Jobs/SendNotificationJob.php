<?php
namespace Modules\Notification\Infrastructure\Jobs;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Modules\Notification\Infrastructure\Models\NotificationRecordModel;
class SendNotificationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    public int $tries = 3;
    public int $backoff = 60;
    public int $timeout = 120;
    public function __construct(
        public readonly string $tenantId,
        public readonly string $userId,
        public readonly string $type,
        public readonly string $channel,
        public readonly array $data,
    ) {}
    public function handle(): void
    {
        $record = NotificationRecordModel::create([
            'id' => (string) \Illuminate\Support\Str::uuid(),
            'tenant_id' => $this->tenantId,
            'user_id' => $this->userId,
            'type' => $this->type,
            'channel' => $this->channel,
            'data' => $this->data,
            'status' => 'sent',
        ]);
        // Channel-specific dispatch logic would be added here per channel type
    }
}
