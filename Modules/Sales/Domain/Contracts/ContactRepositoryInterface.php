<?php

declare(strict_types=1);

namespace Modules\Sales\Domain\Contracts;

use Modules\Sales\Domain\Entities\Contact;

interface ContactRepositoryInterface
{
    public function findById(int $id): ?Contact;

    /** @return Contact[] */
    public function findAll(?string $type, int $page = 1, int $perPage = 25): array;

    public function save(Contact $contact): Contact;

    public function delete(int $id): void;
}
