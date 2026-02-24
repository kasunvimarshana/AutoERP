<?php
namespace Modules\CRM\Presentation\Controllers;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Modules\CRM\Infrastructure\Repositories\ContactRepository;
use Modules\CRM\Presentation\Requests\StoreContactRequest;
use Modules\Shared\Application\ResponseFormatter;
class ContactController extends Controller
{
    public function __construct(private ContactRepository $repo) {}
    public function index(): JsonResponse
    {
        return ResponseFormatter::paginated($this->repo->paginate(request()->all(), 15));
    }
    public function store(StoreContactRequest $request): JsonResponse
    {
        $contact = $this->repo->create($request->validated());
        return ResponseFormatter::success($contact, 'Contact created.', 201);
    }
    public function show(string $id): JsonResponse
    {
        $contact = $this->repo->findById($id);
        if (!$contact) return ResponseFormatter::error('Not found.', [], 404);
        return ResponseFormatter::success($contact);
    }
    public function update(StoreContactRequest $request, string $id): JsonResponse
    {
        $contact = $this->repo->update($id, $request->validated());
        return ResponseFormatter::success($contact, 'Updated.');
    }
    public function destroy(string $id): JsonResponse
    {
        $this->repo->delete($id);
        return ResponseFormatter::success(null, 'Deleted.');
    }
}
