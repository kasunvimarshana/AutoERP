<?php

declare(strict_types=1);

namespace Modules\HR\Application\UseCases;

use Modules\HR\Domain\Entities\PayrollRecord;
use Modules\HR\Domain\Events\PayrollRecordProcessed;
use Modules\HR\Domain\Exceptions\PayrollRecordNotFoundException;
use Modules\HR\Domain\RepositoryInterfaces\PayrollRepositoryInterface;

class ProcessPayrollRecord
{
    public function __construct(private readonly PayrollRepositoryInterface $repo) {}

    public function execute(int $id): PayrollRecord
    {
        $record = $this->repo->find($id);
        if (! $record) {
            throw new PayrollRecordNotFoundException($id);
        }

        $record->process();

        $saved = $this->repo->save($record);
        PayrollRecordProcessed::dispatch($saved);

        return $saved;
    }
}
