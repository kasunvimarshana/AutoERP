<?php

namespace Modules\Media\Application\UseCases;

use Illuminate\Contracts\Filesystem\Factory as FilesystemFactory;
use Modules\Media\Domain\Contracts\MediaRepositoryInterface;

class DeleteMediaUseCase
{
    public function __construct(
        private MediaRepositoryInterface $repository,
        private FilesystemFactory $filesystem,
    ) {}

    public function execute(string $id): void
    {
        $media = $this->repository->findById($id);

        if (! $media) {
            throw new \DomainException('Media file not found.');
        }

        $this->filesystem->disk($media->disk)->delete($media->path);

        $this->repository->delete($id);
    }
}
