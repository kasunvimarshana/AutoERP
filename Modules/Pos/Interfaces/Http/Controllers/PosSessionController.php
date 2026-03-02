<?php

declare(strict_types=1);

namespace Modules\Pos\Interfaces\Http\Controllers;

use App\Shared\Abstractions\BaseController;
use Illuminate\Http\JsonResponse;
use Modules\Pos\Application\Commands\ClosePosSessionCommand;
use Modules\Pos\Application\Commands\DeletePosSessionCommand;
use Modules\Pos\Application\Commands\OpenPosSessionCommand;
use Modules\Pos\Application\Services\PosSessionService;
use Modules\Pos\Interfaces\Http\Requests\ClosePosSessionRequest;
use Modules\Pos\Interfaces\Http\Requests\OpenPosSessionRequest;
use Modules\Pos\Interfaces\Http\Resources\PosSessionResource;

class PosSessionController extends BaseController
{
    public function __construct(
        private readonly PosSessionService $service,
    ) {}

    public function index(): JsonResponse
    {
        $tenantId = (int) request('tenant_id');
        $page = (int) request('page', 1);
        $perPage = (int) request('per_page', 25);

        $result = $this->service->findAll($tenantId, $page, $perPage);

        return $this->success(
            data: array_map(
                fn ($s) => (new PosSessionResource($s))->resolve(),
                $result['items']
            ),
            message: 'POS sessions retrieved successfully',
            meta: [
                'current_page' => $result['current_page'],
                'last_page' => $result['last_page'],
                'per_page' => $result['per_page'],
                'total' => $result['total'],
            ],
        );
    }

    public function store(OpenPosSessionRequest $request): JsonResponse
    {
        try {
            $session = $this->service->openSession(new OpenPosSessionCommand(
                tenantId: $request->validated('tenant_id'),
                userId: $request->validated('user_id'),
                openingFloat: (string) $request->validated('opening_float'),
                currency: $request->validated('currency'),
                notes: $request->validated('notes'),
            ));
        } catch (\DomainException $e) {
            return $this->error($e->getMessage(), status: 422);
        }

        return $this->success(
            data: (new PosSessionResource($session))->resolve(),
            message: 'POS session opened successfully',
            status: 201,
        );
    }

    public function show(int $id): JsonResponse
    {
        $tenantId = (int) request('tenant_id');
        $session = $this->service->findById($id, $tenantId);

        if ($session === null) {
            return $this->error('POS session not found', status: 404);
        }

        return $this->success(
            data: (new PosSessionResource($session))->resolve(),
            message: 'POS session retrieved successfully',
        );
    }

    public function close(ClosePosSessionRequest $request, int $id): JsonResponse
    {
        try {
            $session = $this->service->closeSession(new ClosePosSessionCommand(
                id: $id,
                tenantId: $request->validated('tenant_id'),
                closingFloat: (string) $request->validated('closing_float'),
                notes: $request->validated('notes'),
            ));
        } catch (\DomainException $e) {
            return $this->error($e->getMessage(), status: 422);
        }

        return $this->success(
            data: (new PosSessionResource($session))->resolve(),
            message: 'POS session closed successfully',
        );
    }

    public function destroy(int $id): JsonResponse
    {
        $tenantId = (int) request('tenant_id');

        try {
            $this->service->deleteSession(new DeletePosSessionCommand($id, $tenantId));

            return $this->success(message: 'POS session deleted successfully');
        } catch (\DomainException $e) {
            return $this->error($e->getMessage(), status: 404);
        }
    }
}
