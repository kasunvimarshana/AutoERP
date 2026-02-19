<?php

namespace App\Services;

use App\Enums\NotificationStatus;
use App\Models\NotificationLog;
use App\Models\NotificationTemplate;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class NotificationService
{
    public function paginateTemplates(string $tenantId, int $perPage = 15): LengthAwarePaginator
    {
        return NotificationTemplate::where('tenant_id', $tenantId)
            ->orderBy('name')
            ->paginate($perPage);
    }

    public function createTemplate(array $data): NotificationTemplate
    {
        return DB::transaction(function () use ($data) {
            return NotificationTemplate::create($data);
        });
    }

    public function updateTemplate(string $id, array $data): NotificationTemplate
    {
        return DB::transaction(function () use ($id, $data) {
            $template = NotificationTemplate::findOrFail($id);
            $template->update($data);

            return $template->fresh();
        });
    }

    public function send(
        string $tenantId,
        string $templateSlug,
        string $notifiableType,
        string $notifiableId,
        array $variables = [],
        array $metadata = []
    ): NotificationLog {
        $template = NotificationTemplate::where('tenant_id', $tenantId)
            ->where('slug', $templateSlug)
            ->firstOrFail();

        $body = $template->body;
        $subject = $template->subject;

        foreach ($variables as $key => $value) {
            $body = str_replace('{{'.$key.'}}', $value, $body);
            if ($subject !== null) {
                $subject = str_replace('{{'.$key.'}}', $value, $subject);
            }
        }

        return NotificationLog::create([
            'tenant_id' => $tenantId,
            'template_id' => $template->id,
            'notifiable_type' => $notifiableType,
            'notifiable_id' => $notifiableId,
            'channel' => $template->channel,
            'subject' => $subject,
            'body' => $body,
            'status' => NotificationStatus::Sent,
            'sent_at' => now(),
            'metadata' => $metadata ?: null,
        ]);
    }

    public function paginateLogs(string $tenantId, array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = NotificationLog::where('tenant_id', $tenantId)
            ->with(['template']);

        if (isset($filters['channel'])) {
            $query->where('channel', $filters['channel']);
        }
        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }
        if (isset($filters['notifiable_type'])) {
            $query->where('notifiable_type', $filters['notifiable_type']);
        }
        if (isset($filters['date_from'])) {
            $query->whereDate('created_at', '>=', $filters['date_from']);
        }
        if (isset($filters['date_to'])) {
            $query->whereDate('created_at', '<=', $filters['date_to']);
        }

        return $query->orderByDesc('created_at')->paginate($perPage);
    }
}
