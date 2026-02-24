<?php

namespace Modules\DocumentManagement\Application\UseCases;

use Illuminate\Support\Facades\DB;
use Modules\DocumentManagement\Domain\Contracts\DocumentRepositoryInterface;

class CreateDocumentUseCase
{
    public function __construct(
        private DocumentRepositoryInterface $documentRepo,
    ) {}

    public function execute(array $data): object
    {
        return DB::transaction(function () use ($data) {
            return $this->documentRepo->create([
                'tenant_id'   => $data['tenant_id'],
                'category_id' => $data['category_id'] ?? null,
                'title'       => $data['title'],
                'description' => $data['description'] ?? null,
                'file_path'   => $data['file_path'] ?? null,
                'mime_type'   => $data['mime_type'] ?? null,
                'file_size'   => $data['file_size'] ?? null,
                'status'      => 'draft',
                'owner_id'    => $data['owner_id'] ?? null,
            ]);
        });
    }
}
