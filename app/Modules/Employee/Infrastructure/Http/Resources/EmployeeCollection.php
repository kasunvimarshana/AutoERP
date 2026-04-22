<?php

declare(strict_types=1);

namespace Modules\Employee\Infrastructure\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

class EmployeeCollection extends ResourceCollection
{
    /** @var class-string<EmployeeResource> */
    public $collects = EmployeeResource::class;

    public function toArray(Request $request): array
    {
        return $this->collection
            ->map(static function (mixed $employee) use ($request): array {
                if ($employee instanceof EmployeeResource) {
                    return $employee->toArray($request);
                }

                return (new EmployeeResource($employee))->toArray($request);
            })
            ->all();
    }
}
