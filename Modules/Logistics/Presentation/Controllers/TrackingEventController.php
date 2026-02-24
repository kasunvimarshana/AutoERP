<?php

namespace Modules\Logistics\Presentation\Controllers;

use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Modules\Logistics\Application\UseCases\AddTrackingEventUseCase;
use Modules\Logistics\Presentation\Requests\AddTrackingEventRequest;
use Modules\Shared\Application\ResponseFormatter;

class TrackingEventController extends Controller
{
    public function __construct(
        private AddTrackingEventUseCase $addUseCase,
    ) {}

    public function store(AddTrackingEventRequest $request): JsonResponse
    {
        try {
            $event = $this->addUseCase->execute($request->validated());
            return ResponseFormatter::success($event, 'Tracking event added.', 201);
        } catch (ModelNotFoundException $e) {
            return ResponseFormatter::error($e->getMessage(), [], 404);
        }
    }
}
