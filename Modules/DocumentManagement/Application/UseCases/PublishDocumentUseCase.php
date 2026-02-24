<?php

namespace Modules\DocumentManagement\Application\UseCases;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Modules\DocumentManagement\Domain\Contracts\DocumentRepositoryInterface;
use Modules\DocumentManagement\Domain\Events\DocumentPublished;

class PublishDocumentUseCase
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

            if ($document->status === 'published') {
                throw new \DomainException('Document is already published.');
            }

            if ($document->status === 'archived') {
                throw new \DomainException('Archived documents cannot be published.');
            }

            $document = $this->documentRepo->update($documentId, [
                'status'       => 'published',
                'published_at' => now(),
            ]);

            Event::dispatch(new DocumentPublished(
                $document->id,
                $document->tenant_id,
                $document->title,
            ));

            return $document;
        });
    }
}
