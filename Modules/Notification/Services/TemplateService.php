<?php

declare(strict_types=1);

namespace Modules\Notification\Services;

use Modules\Core\Helpers\TransactionHelper;
use Modules\Notification\Exceptions\InvalidTemplateDataException;
use Modules\Notification\Models\NotificationTemplate;
use Modules\Notification\Repositories\NotificationTemplateRepository;

/**
 * Template Service
 *
 * Handles notification template management and rendering
 */
class TemplateService
{
    public function __construct(
        private NotificationTemplateRepository $templateRepository
    ) {}

    /**
     * Render template with data
     */
    public function render(string $templateCode, array $data = []): array
    {
        $template = $this->templateRepository->findByCode($templateCode);

        return $template->render($data);
    }

    /**
     * Validate data against template requirements
     *
     * @throws InvalidTemplateDataException
     */
    public function validate(string $templateCode, array $data): bool
    {
        $template = $this->templateRepository->findByCode($templateCode);

        if (! $template->validateData($data)) {
            $missing = $this->getMissingRequiredVariables($template, $data);
            throw new InvalidTemplateDataException(
                'Missing required template variables: '.implode(', ', $missing)
            );
        }

        return true;
    }

    /**
     * Create a new template
     */
    public function create(array $data): NotificationTemplate
    {
        return TransactionHelper::execute(function () use ($data) {
            // Check if code already exists
            if ($this->templateRepository->codeExists($data['code'])) {
                throw new \InvalidArgumentException("Template code '{$data['code']}' already exists");
            }

            // Set defaults
            $data['is_active'] = $data['is_active'] ?? true;
            $data['is_system'] = $data['is_system'] ?? false;
            $data['tenant_id'] = $data['tenant_id'] ?? auth()->user()?->tenant_id;
            $data['organization_id'] = $data['organization_id'] ?? auth()->user()?->organization_id;

            return $this->templateRepository->create($data);
        });
    }

    /**
     * Update an existing template
     */
    public function update(int $templateId, array $data): NotificationTemplate
    {
        return TransactionHelper::execute(function () use ($templateId, $data) {
            $template = $this->templateRepository->findById($templateId);

            // Check if trying to modify system template
            if ($template->is_system && isset($data['code'])) {
                throw new \InvalidArgumentException('Cannot modify code of system template');
            }

            // Check if code already exists (for other templates)
            if (isset($data['code']) && $this->templateRepository->codeExists($data['code'], $templateId)) {
                throw new \InvalidArgumentException("Template code '{$data['code']}' already exists");
            }

            return $this->templateRepository->update($templateId, $data);
        });
    }

    /**
     * Delete a template
     */
    public function delete(int $templateId): bool
    {
        return TransactionHelper::execute(function () use ($templateId) {
            $template = $this->templateRepository->findById($templateId);

            // Prevent deletion of system templates
            if ($template->is_system) {
                throw new \InvalidArgumentException('Cannot delete system template');
            }

            return $template->delete();
        });
    }

    /**
     * Activate/deactivate a template
     */
    public function toggleActive(int $templateId, bool $isActive): NotificationTemplate
    {
        return TransactionHelper::execute(function () use ($templateId, $isActive) {
            return $this->templateRepository->update($templateId, [
                'is_active' => $isActive,
            ]);
        });
    }

    /**
     * Get missing required variables from data
     */
    private function getMissingRequiredVariables(NotificationTemplate $template, array $data): array
    {
        $missing = [];

        if (empty($template->variables)) {
            return $missing;
        }

        foreach ($template->variables as $variable) {
            if (($variable['required'] ?? false) && ! isset($data[$variable['name']])) {
                $missing[] = $variable['name'];
            }
        }

        return $missing;
    }

    /**
     * Preview template rendering without creating notification
     */
    public function preview(string $templateCode, array $data = []): array
    {
        // Validate first
        $this->validate($templateCode, $data);

        // Render and return
        return $this->render($templateCode, $data);
    }
}
