<?php

declare(strict_types=1);

namespace Modules\Barcode\Domain\RepositoryInterfaces;

use Modules\Barcode\Domain\Entities\LabelTemplate;

interface LabelTemplateRepositoryInterface
{
    public function findById(int $id): ?LabelTemplate;

    /** @return LabelTemplate[] */
    public function findAll(int $tenantId): array;

    /** @return LabelTemplate[] */
    public function findActive(int $tenantId): array;

    public function findByFormat(int $tenantId, string $format): array;

    public function save(LabelTemplate $template): LabelTemplate;

    public function delete(int $id): void;
}
