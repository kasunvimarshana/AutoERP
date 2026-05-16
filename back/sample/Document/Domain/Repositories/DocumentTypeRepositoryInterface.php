<?php

namespace Modules\Document\Domain\Repositories;

interface DocumentTypeRepositoryInterface
{
    public function findCodeById(int $documentTypeId): ?string;

    public function findDefaultStatusById(int $documentTypeId): ?string;
}
