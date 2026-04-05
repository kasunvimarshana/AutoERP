<?php

declare(strict_types=1);

namespace Modules\Accounting\Infrastructure\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Accounting\Application\Contracts\CreateRefundServiceInterface;
use Modules\Accounting\Domain\RepositoryInterfaces\RefundRepositoryInterface;
use Illuminate\Routing\Controller;

class RefundController extends Controller
{
    public function __construct(
        private readonly CreateRefundServiceInterface $createService,
        private readonly RefundRepositoryInterface $repo,
    ) {}

    public function index(Request $request): JsonResponse
    {
        return response()->json($this->repo->findByTenant(
            (int) $request->input('tenant_id'),
            (int) $request->input('per_page', 15),
            (int) $request->input('page', 1)
        ));
    }

    public function show(int $id): JsonResponse
    {
        $r = $this->repo->findById($id);
        if (!$r) return response()->json(['message' => 'Not found.'], 404);
        return response()->json($r);
    }

    public function store(Request $request): JsonResponse
    {
        return response()->json($this->createService->execute($request->all()), 201);
    }
}
