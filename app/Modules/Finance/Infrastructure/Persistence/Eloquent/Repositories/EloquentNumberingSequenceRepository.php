<?php

declare(strict_types=1);

namespace Modules\Finance\Infrastructure\Persistence\Eloquent\Repositories;

use Illuminate\Support\Facades\DB;
use Modules\Core\Infrastructure\Persistence\Repositories\EloquentRepository;
use Modules\Finance\Domain\Entities\NumberingSequence;
use Modules\Finance\Domain\RepositoryInterfaces\NumberingSequenceRepositoryInterface;
use Modules\Finance\Infrastructure\Persistence\Eloquent\Models\NumberingSequenceModel;

class EloquentNumberingSequenceRepository extends EloquentRepository implements NumberingSequenceRepositoryInterface
{
    public function __construct(NumberingSequenceModel $model)
    {
        parent::__construct($model);
        $this->setDomainEntityMapper(fn (NumberingSequenceModel $model): NumberingSequence => $this->mapModelToDomainEntity($model));
    }

    public function save(NumberingSequence $sequence): NumberingSequence
    {
        $data = [
            'tenant_id' => $sequence->getTenantId(),
            'module' => $sequence->getModule(),
            'document_type' => $sequence->getDocumentType(),
            'prefix' => $sequence->getPrefix(),
            'suffix' => $sequence->getSuffix(),
            'next_number' => $sequence->getNextNumber(),
            'padding' => $sequence->getPadding(),
            'is_active' => $sequence->isActive(),
        ];

        if ($sequence->getId()) {
            $model = $this->update($sequence->getId(), $data);
        } else {
            $model = $this->create($data);
        }

        /** @var NumberingSequenceModel $model */
        return $this->toDomainEntity($model);
    }

    public function findByTenantModuleAndDocumentType(int $tenantId, string $module, string $documentType): ?NumberingSequence
    {
        /** @var NumberingSequenceModel|null $model */
        $model = $this->model->newQuery()
            ->withoutGlobalScope('tenant')
            ->where('tenant_id', $tenantId)
            ->where('module', $module)
            ->where('document_type', $documentType)
            ->first();

        return $model ? $this->toDomainEntity($model) : null;
    }

    public function generateNextNumber(int $tenantId, string $module, string $documentType): string
    {
        return DB::transaction(function () use ($tenantId, $module, $documentType): string {
            /** @var NumberingSequenceModel|null $model */
            $model = $this->model->newQuery()
                ->withoutGlobalScope('tenant')
                ->where('tenant_id', $tenantId)
                ->where('module', $module)
                ->where('document_type', $documentType)
                ->lockForUpdate()
                ->first();

            if (! $model) {
                $prefix = strtoupper(substr($documentType, 0, 3));

                return $prefix.'-'.str_pad('1', 5, '0', STR_PAD_LEFT);
            }

            $number = str_pad((string) $model->next_number, (int) $model->padding, '0', STR_PAD_LEFT);
            $result = ($model->prefix ?? '').$number.($model->suffix ?? '');

            $model->increment('next_number');

            return $result;
        });
    }

    private function mapModelToDomainEntity(NumberingSequenceModel $model): NumberingSequence
    {
        return new NumberingSequence(
            tenantId: (int) $model->tenant_id,
            module: (string) $model->module,
            documentType: (string) $model->document_type,
            prefix: $model->prefix,
            suffix: $model->suffix,
            nextNumber: (int) $model->next_number,
            padding: (int) $model->padding,
            isActive: (bool) $model->is_active,
            id: (int) $model->id,
            createdAt: $model->created_at,
            updatedAt: $model->updated_at,
        );
    }
}
