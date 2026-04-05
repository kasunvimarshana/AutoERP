<?php

declare(strict_types=1);

namespace Modules\Barcode\Application\Services;

use Modules\Barcode\Application\Contracts\ManageLabelTemplateServiceInterface;
use Modules\Barcode\Domain\Entities\LabelTemplate;
use Modules\Barcode\Domain\Exceptions\BarcodeNotFoundException;
use Modules\Barcode\Domain\RepositoryInterfaces\LabelTemplateRepositoryInterface;

class ManageLabelTemplateService implements ManageLabelTemplateServiceInterface
{
    private const VALID_FORMATS = ['zpl', 'epl', 'svg'];

    public function __construct(
        private readonly LabelTemplateRepositoryInterface $templates,
    ) {}

    public function create(
        int    $tenantId,
        string $name,
        string $format,
        string $content,
        array  $defaultVariables,
    ): LabelTemplate {
        $this->assertValidFormat($format);

        $now      = new \DateTime();
        $template = new LabelTemplate(
            null,
            $tenantId,
            $name,
            $format,
            $content,
            $defaultVariables,
            true,
            $now,
            $now,
        );

        return $this->templates->save($template);
    }

    public function getById(int $id): LabelTemplate
    {
        $template = $this->templates->findById($id);

        if ($template === null) {
            throw BarcodeNotFoundException::withId($id);
        }

        return $template;
    }

    /** @return LabelTemplate[] */
    public function listAll(int $tenantId): array
    {
        return $this->templates->findAll($tenantId);
    }

    /** @return LabelTemplate[] */
    public function listActive(int $tenantId): array
    {
        return $this->templates->findActive($tenantId);
    }

    public function updateContent(int $id, string $content, string $format): LabelTemplate
    {
        $this->assertValidFormat($format);

        $template = $this->getById($id);
        $template->updateContent($content, $format);

        return $this->templates->save($template);
    }

    public function activate(int $id): LabelTemplate
    {
        $template = $this->getById($id);
        $template->activate();

        return $this->templates->save($template);
    }

    public function deactivate(int $id): LabelTemplate
    {
        $template = $this->getById($id);
        $template->deactivate();

        return $this->templates->save($template);
    }

    public function delete(int $id): void
    {
        $this->getById($id); // throws if not found
        $this->templates->delete($id);
    }

    // ── Private helpers ───────────────────────────────────────────────────────

    private function assertValidFormat(string $format): void
    {
        if (!in_array($format, self::VALID_FORMATS, true)) {
            throw new \InvalidArgumentException(
                sprintf(
                    'Invalid label template format "%s". Supported: %s.',
                    $format,
                    implode(', ', self::VALID_FORMATS),
                )
            );
        }
    }
}
