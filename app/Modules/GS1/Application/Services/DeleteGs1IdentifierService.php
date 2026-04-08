<?php

declare(strict_types=1);

namespace Modules\GS1\Application\Services;

use Modules\Core\Application\Services\BaseService;
use Modules\GS1\Application\Contracts\DeleteGs1IdentifierServiceInterface;
use Modules\GS1\Domain\Entities\Gs1Identifier;
use Modules\GS1\Domain\Events\Gs1IdentifierDeleted;
use Modules\GS1\Domain\Exceptions\Gs1IdentifierNotFoundException;
use Modules\GS1\Domain\RepositoryInterfaces\Gs1IdentifierRepositoryInterface;

class DeleteGs1IdentifierService extends BaseService implements DeleteGs1IdentifierServiceInterface
{
    public function __construct(private readonly Gs1IdentifierRepositoryInterface $identifierRepository)
    {
        parent::__construct($identifierRepository);
    }

    protected function handle(array $data): bool
    {
        $id = $data['id'];

        /** @var Gs1Identifier|null $identifier */
        $identifier = $this->identifierRepository->find($id);
        if (! $identifier) {
            throw new Gs1IdentifierNotFoundException($id);
        }

        $this->identifierRepository->delete($id);
        $this->addEvent(new Gs1IdentifierDeleted($identifier));

        return true;
    }
}
