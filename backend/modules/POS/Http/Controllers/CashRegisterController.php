<?php

declare(strict_types=1);

namespace Modules\POS\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Core\Http\Controllers\BaseController;
use Modules\POS\Services\CashRegisterService;
use Modules\POS\Repositories\CashRegisterRepository;

class CashRegisterController extends BaseController
{
    public function __construct(
        private CashRegisterService $cashRegisterService,
        private CashRegisterRepository $cashRegisterRepository
    ) {}

    public function index(): JsonResponse
    {
        $registers = $this->cashRegisterRepository->all();
        return $this->success($registers);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'location_id' => 'required|uuid|exists:pos_business_locations,id',
            'name' => 'required|string|max:255',
        ]);

        $register = $this->cashRegisterRepository->create($validated);

        return $this->success($register, 'Cash register created successfully', 201);
    }

    public function show(string $id): JsonResponse
    {
        $register = $this->cashRegisterRepository->findById($id);

        if (!$register) {
            return $this->error('Cash register not found', 404);
        }

        return $this->success($register);
    }

    public function open(Request $request, string $id): JsonResponse
    {
        $register = $this->cashRegisterRepository->findById($id);

        if (!$register) {
            return $this->error('Cash register not found', 404);
        }

        $validated = $request->validate([
            'opening_balance' => 'required|numeric|min:0',
            'user_id' => 'required|uuid',
        ]);

        $opened = $this->cashRegisterService->openRegister(
            $register,
            $validated['opening_balance'],
            $validated['user_id']
        );

        return $this->success($opened, 'Cash register opened successfully');
    }

    public function close(Request $request, string $id): JsonResponse
    {
        $register = $this->cashRegisterRepository->findById($id);

        if (!$register) {
            return $this->error('Cash register not found', 404);
        }

        $validated = $request->validate([
            'closing_balance' => 'required|numeric|min:0',
        ]);

        $closed = $this->cashRegisterService->closeRegister(
            $register,
            $validated['closing_balance']
        );

        return $this->success($closed, 'Cash register closed successfully');
    }

    public function currentBalance(string $id): JsonResponse
    {
        $register = $this->cashRegisterRepository->findById($id);

        if (!$register) {
            return $this->error('Cash register not found', 404);
        }

        $balance = $this->cashRegisterService->getCurrentBalance($register);
        $expected = $this->cashRegisterService->getExpectedBalance($register);

        return $this->success([
            'current_balance' => $balance,
            'expected_balance' => $expected,
            'difference' => $balance - $expected,
        ]);
    }
}
