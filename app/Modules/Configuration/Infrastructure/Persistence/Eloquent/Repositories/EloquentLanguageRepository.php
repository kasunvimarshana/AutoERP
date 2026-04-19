<?php

declare(strict_types=1);

namespace Modules\Configuration\Infrastructure\Persistence\Eloquent\Repositories;

use Modules\Core\Infrastructure\Persistence\Repositories\EloquentRepository;
use Modules\Configuration\Domain\Entities\Language;
use Modules\Configuration\Domain\RepositoryInterfaces\LanguageRepositoryInterface;
use Modules\Configuration\Infrastructure\Persistence\Eloquent\Models\LanguageModel;

class EloquentLanguageRepository extends EloquentRepository implements LanguageRepositoryInterface
{
    public function __construct(LanguageModel $model)
    {
        parent::__construct($model);
        $this->setDomainEntityMapper(fn (LanguageModel $m): Language => $this->mapToDomain($m));
    }

    public function findByCode(string $code): ?Language
    {
        $model = $this->model->where('code', $code)->first();

        return $model ? $this->toDomainEntity($model) : null;
    }

    public function save(Language $language): Language
    {
        $data = [
            'code' => $language->getCode(),
            'name' => $language->getName(),
        ];

        $model = $language->getId()
            ? $this->update($language->getId(), $data)
            : $this->create($data);

        /** @var LanguageModel $model */
        return $this->mapToDomain($model);
    }

    private function mapToDomain(LanguageModel $model): Language
    {
        return new Language(
            code: $model->code,
            name: $model->name,
            id: $model->id,
            createdAt: $model->created_at,
            updatedAt: $model->updated_at,
        );
    }
}
