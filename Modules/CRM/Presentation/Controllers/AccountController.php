<?php
namespace Modules\CRM\Presentation\Controllers;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\CRM\Infrastructure\Models\AccountModel;
use Modules\Shared\Application\ResponseFormatter;
class AccountController extends Controller
{
    public function index(): JsonResponse
    {
        $accounts = AccountModel::latest()->paginate(15);
        return ResponseFormatter::paginated($accounts);
    }
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'industry' => 'nullable|string|max:100',
            'website' => 'nullable|url|max:255',
            'phone' => 'nullable|string|max:50',
            'email' => 'nullable|email|max:255',
            'address' => 'nullable|array',
            'tags' => 'nullable|array',
            'account_manager_id' => 'nullable|uuid',
        ]);
        $account = AccountModel::create($data);
        return ResponseFormatter::success($account, 'Account created.', 201);
    }
    public function show(string $id): JsonResponse
    {
        $account = AccountModel::find($id);
        if (!$account) return ResponseFormatter::error('Not found.', [], 404);
        return ResponseFormatter::success($account);
    }
    public function update(Request $request, string $id): JsonResponse
    {
        $account = AccountModel::findOrFail($id);
        $data = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'industry' => 'nullable|string|max:100',
            'website' => 'nullable|url|max:255',
            'phone' => 'nullable|string|max:50',
            'email' => 'nullable|email|max:255',
            'address' => 'nullable|array',
            'tags' => 'nullable|array',
            'account_manager_id' => 'nullable|uuid',
        ]);
        $account->update($data);
        return ResponseFormatter::success($account->fresh(), 'Updated.');
    }
    public function destroy(string $id): JsonResponse
    {
        AccountModel::findOrFail($id)->delete();
        return ResponseFormatter::success(null, 'Deleted.');
    }
}
