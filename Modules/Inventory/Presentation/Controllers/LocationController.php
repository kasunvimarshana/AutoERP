<?php
namespace Modules\Inventory\Presentation\Controllers;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Inventory\Infrastructure\Models\LocationModel;
use Modules\Inventory\Domain\Enums\LocationType;
use Modules\Shared\Application\ResponseFormatter;
class LocationController extends Controller
{
    public function index(): JsonResponse
    {
        $locations = LocationModel::latest()->paginate(15);
        return ResponseFormatter::paginated($locations);
    }
    public function store(Request $request): JsonResponse
    {
        $types = implode(',', array_column(LocationType::cases(), 'value'));
        $data = $request->validate([
            'warehouse_id' => 'required|uuid',
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:50',
            'type' => 'nullable|in:'.$types,
            'parent_id' => 'nullable|uuid',
            'is_active' => 'nullable|boolean',
        ]);
        $location = LocationModel::create($data);
        return ResponseFormatter::success($location, 'Location created.', 201);
    }
    public function show(string $id): JsonResponse
    {
        $location = LocationModel::with('children')->find($id);
        if (!$location) return ResponseFormatter::error('Not found.', [], 404);
        return ResponseFormatter::success($location);
    }
    public function update(Request $request, string $id): JsonResponse
    {
        $location = LocationModel::findOrFail($id);
        $types = implode(',', array_column(LocationType::cases(), 'value'));
        $data = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'code' => 'sometimes|required|string|max:50',
            'type' => 'nullable|in:'.$types,
            'parent_id' => 'nullable|uuid',
            'is_active' => 'nullable|boolean',
        ]);
        $location->update($data);
        return ResponseFormatter::success($location->fresh(), 'Updated.');
    }
    public function destroy(string $id): JsonResponse
    {
        LocationModel::findOrFail($id)->delete();
        return ResponseFormatter::success(null, 'Deleted.');
    }
}
