<?php

declare(strict_types=1);

namespace Modules\Barcode\Application\Contracts;

use Modules\Barcode\Domain\Entities\LabelTemplate;

interface ManageLabelTemplateServiceInterface
{
    public function create(
        int     $tenantId,
        string  $name,
        string  $format,
        string  $content,
        array   $defaultVariables,
    ): LabelTemplate;

    public function getById(int $id): LabelTemplate;

    /** @return LabelTemplate[] */
    public function listAll(int $tenantId): array;

    /** @return LabelTemplate[] */
    public function listActive(int $tenantId): array;

    public function updateContent(int $id, string $content, string $format): LabelTemplate;

    public function activate(int $id): LabelTemplate;

    public function deactivate(int $id): LabelTemplate;

    public function delete(int $id): void;
}
