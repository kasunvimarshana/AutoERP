<?php declare(strict_types=1);

namespace Modules\Rental\Infrastructure\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Rental\Application\Contracts\ManageRentalAgreementServiceInterface;
use Modules\Rental\Infrastructure\Http\Requests\CreateRentalAgreementRequest;
use Modules\Rental\Infrastructure\Http\Resources\RentalAgreementResource;

class RentalAgreementController extends Controller
{
    public function __construct(
        private readonly ManageRentalAgreementServiceInterface $service,
    ) {}

    public function create(CreateRentalAgreementRequest $request): JsonResponse
    {
        $agreement = $this->service->create($request->validated());
        return response()->json(new RentalAgreementResource($agreement), 201);
    }

    public function show(Request $request, string $id): JsonResponse
    {
        $tenantId = (int) $request->header('X-Tenant-ID');
        $agreement = $this->service->find($tenantId, $id);
        return response()->json(new RentalAgreementResource($agreement));
    }

    public function active(Request $request): JsonResponse
    {
        $tenantId = (int) $request->header('X-Tenant-ID');
        $agreements = $this->service->getActive($tenantId);
        return response()->json(['data' => $agreements]);
    }
}
