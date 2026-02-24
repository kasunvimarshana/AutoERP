<?php

namespace Modules\Recruitment\Presentation\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Modules\Recruitment\Application\UseCases\CreateJobApplicationUseCase;
use Modules\Recruitment\Application\UseCases\HireApplicantUseCase;
use Modules\Recruitment\Application\UseCases\RejectApplicantUseCase;
use Modules\Recruitment\Domain\Contracts\JobApplicationRepositoryInterface;
use Modules\Recruitment\Presentation\Requests\HireApplicantRequest;
use Modules\Recruitment\Presentation\Requests\RejectApplicantRequest;
use Modules\Recruitment\Presentation\Requests\StoreJobApplicationRequest;

class JobApplicationController extends Controller
{
    public function __construct(
        private JobApplicationRepositoryInterface $applicationRepo,
        private CreateJobApplicationUseCase       $createUseCase,
        private HireApplicantUseCase              $hireUseCase,
        private RejectApplicantUseCase            $rejectUseCase,
    ) {}

    public function index(): JsonResponse
    {
        return response()->json($this->applicationRepo->findByTenant(auth()->user()?->tenant_id));
    }

    public function store(StoreJobApplicationRequest $request): JsonResponse
    {
        $application = $this->createUseCase->execute(
            array_merge($request->validated(), ['tenant_id' => auth()->user()?->tenant_id])
        );

        return response()->json($application, 201);
    }

    public function show(string $id): JsonResponse
    {
        $application = $this->applicationRepo->findById($id);

        if (! $application) {
            return response()->json(['message' => 'Not found.'], 404);
        }

        return response()->json($application);
    }

    public function hire(HireApplicantRequest $request, string $id): JsonResponse
    {
        $application = $this->hireUseCase->execute($id, $request->validated()['reviewer_id']);

        return response()->json($application);
    }

    public function reject(RejectApplicantRequest $request, string $id): JsonResponse
    {
        $validated   = $request->validated();
        $application = $this->rejectUseCase->execute(
            $id,
            $validated['reviewer_id'],
            $validated['rejection_reason'] ?? null,
        );

        return response()->json($application);
    }

    public function destroy(string $id): JsonResponse
    {
        $this->applicationRepo->delete($id);

        return response()->json(null, 204);
    }
}
