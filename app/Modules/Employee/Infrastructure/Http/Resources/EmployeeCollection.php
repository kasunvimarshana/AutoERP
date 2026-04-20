<?php

declare(strict_types=1);

namespace Modules\Employee\Infrastructure\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

class EmployeeCollection extends ResourceCollection
{
    /** @var class-string<\Modules\Employee\Infrastructure\Http\Resources\EmployeeResource> */
    public $collects = \Modules\Employee\Infrastructure\Http\Resources\EmployeeResource::class;

    public function toArray(Request $request): array
    {
        return $this->collection
            ->map(static function (mixed $employee) use ($request): array {
                if ($employee instanceof \Modules\Employee\Infrastructure\Http\Resources\EmployeeResource) {
                    return $employee->toArray($request);
                }

                return (new \Modules\Employee\Infrastructure\Http\Resources\EmployeeResource($employee))->toArray($request);
            })
            ->all();
    }
}
