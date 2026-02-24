<?php

namespace Modules\DocumentManagement\Application\UseCases;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Modules\DocumentManagement\Domain\Contracts\DocumentRepositoryInterface;
use Modules\DocumentManagement\Domain\Events\DocumentArchived;

class ArchiveDocumentUseCase
{
    public function __construct(
        private DocumentRepositoryInterface $documentRepo,
    ) {}

    public function execute(string $documentId): object
    {
        return DB::transaction(function () use ($documentId) {
            $document = $this->documentRepo->findById($documentId);

            if (! $document) {
                throw new \DomainException('Document not found.');
            }

            if ($document->status === 'archived') {
                throw new \DomainException('Document is already archived.');
            }

            $document = $this->documentRepo->update($documentId, ['status' => 'archived']);

            Event::dispatch(new DocumentArchived(
                $document->id,
                $document->tenant_id,
            ));

            return $document;
        });
    }
}
