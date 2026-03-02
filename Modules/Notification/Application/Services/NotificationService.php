<?php

declare(strict_types=1);

namespace Modules\Notification\Application\Services;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Modules\Core\Domain\Contracts\ServiceContract;
use Modules\Notification\Application\DTOs\SendNotificationDTO;
use Modules\Notification\Domain\Contracts\NotificationRepositoryContract;
use Modules\Notification\Domain\Entities\NotificationLog;
use Modules\Notification\Domain\Entities\NotificationTemplate;

/**
 * Notification service.
 *
 * Orchestrates template management and notification dispatch.
 * All mutation operations are wrapped in database transactions.
 */
class NotificationService implements ServiceContract
{
    public function __construct(
        private readonly NotificationRepositoryContract $repository,
    ) {}

    /**
     * List all notification templates for the current tenant.
     */
    public function listTemplates(): Collection
    {
        return $this->repository->all();
    }

    /**
     * Create a new notification template.
     *
     * @param array<string, mixed> $data
     */
    public function createTemplate(array $data): NotificationTemplate
    {
        return DB::transaction(function () use ($data): NotificationTemplate {
            /** @var NotificationTemplate $template */
            $template = $this->repository->create($data);

            return $template;
        });
    }

    /**
     * Find a single notification template by ID.
     */
    public function showTemplate(int $id): NotificationTemplate
    {
        /** @var NotificationTemplate $template */
        $template = $this->repository->findOrFail($id);

        return $template;
    }

    /**
     * Delete a notification template by ID.
     */
    public function deleteTemplate(int $id): void
    {
        DB::transaction(function () use ($id): void {
            $this->repository->delete($id);
        });
    }

    /**
     * Update an existing notification template.
     *
     * @param array<string, mixed> $data
     */
    public function updateTemplate(int|string $id, array $data): \Illuminate\Database\Eloquent\Model
    {
        return DB::transaction(function () use ($id, $data): \Illuminate\Database\Eloquent\Model {
            return $this->repository->update($id, $data);
        });
    }

    /**
     * List all notification logs (paginated).
     */
    public function listLogs(int $perPage = 15): \Illuminate\Contracts\Pagination\LengthAwarePaginator
    {
        return $this->repository->paginateLogs($perPage);
    }

    /**
     * Send a notification, optionally using a stored template.
     *
     * If a templateId is provided the body_template is fetched and
     * variables are substituted using {{ variable_name }} placeholders.
     * A NotificationLog is persisted with status='sent'.
     */
    public function sendNotification(SendNotificationDTO $dto): NotificationLog
    {
        return DB::transaction(function () use ($dto): NotificationLog {
            $body    = null;
            $subject = null;

            if ($dto->templateId !== null) {
                /** @var NotificationTemplate $template */
                $template = $this->repository->findOrFail($dto->templateId);
                $body     = $template->body_template;
                $subject  = $template->subject;

                // Escape user-supplied variable values for HTML-rendering channels
                // to prevent stored XSS. SMS and push channels use plain text.
                $needsEscaping = in_array($dto->channel, ['email', 'in_app'], true);

                foreach ($dto->variables as $key => $value) {
                    $placeholder  = '{{ '.$key.' }}';
                    $safeValue    = $needsEscaping
                        ? htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')
                        : (string) $value;
                    $body         = str_replace($placeholder, $safeValue, $body);
                    if ($subject !== null) {
                        $subject = str_replace($placeholder, $safeValue, $subject);
                    }
                }
            }

            /** @var NotificationLog $log */
            $log = NotificationLog::create([
                'channel'                  => $dto->channel,
                'recipient'                => $dto->recipient,
                'notification_template_id' => $dto->templateId,
                'subject'                  => $subject,
                'body'                     => $body,
                'status'                   => 'sent',
                'sent_at'                  => now(),
                'metadata'                 => $dto->metadata,
            ]);

            return $log;
        });
    }
}
