<?php

declare(strict_types=1);

namespace Modules\Accounting\Infrastructure\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Accounting\Application\Contracts\CreatePaymentServiceInterface;
use Modules\Accounting\Domain\RepositoryInterfaces\PaymentRepositoryInterface;
use Illuminate\Routing\Controller;

class PaymentController extends Controller
{
    public function __construct(
        private readonly CreatePaymentServiceInterface $createService,
        private readonly PaymentRepositoryInterface $repo,
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
        $p = $this->repo->findById($id);
        if (!$p) return response()->json(['message' => 'Not found.'], 404);
        return response()->json($p);
    }

    public function store(Request $request): JsonResponse
    {
        return response()->json($this->createService->execute($request->all()), 201);
    }
}
