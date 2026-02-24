<?php

namespace Modules\Helpdesk\Presentation\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Modules\Helpdesk\Application\UseCases\AssignTicketUseCase;
use Modules\Helpdesk\Application\UseCases\CloseTicketUseCase;
use Modules\Helpdesk\Application\UseCases\CreateTicketUseCase;
use Modules\Helpdesk\Application\UseCases\ResolveTicketUseCase;
use Modules\Helpdesk\Domain\Contracts\TicketRepositoryInterface;
use Modules\Helpdesk\Presentation\Requests\AssignTicketRequest;
use Modules\Helpdesk\Presentation\Requests\ResolveTicketRequest;
use Modules\Helpdesk\Presentation\Requests\StoreTicketRequest;

class TicketController extends Controller
{
    public function __construct(
        private TicketRepositoryInterface $ticketRepo,
        private CreateTicketUseCase       $createUseCase,
        private AssignTicketUseCase       $assignUseCase,
        private ResolveTicketUseCase      $resolveUseCase,
        private CloseTicketUseCase        $closeUseCase,
    ) {}

    public function index(): JsonResponse
    {
        return response()->json($this->ticketRepo->findByTenant(auth()->user()?->tenant_id));
    }

    public function store(StoreTicketRequest $request): JsonResponse
    {
        $ticket = $this->createUseCase->execute(
            array_merge($request->validated(), [
                'tenant_id'   => auth()->user()?->tenant_id,
                'reporter_id' => auth()->user()?->id,
            ])
        );

        return response()->json($ticket, 201);
    }

    public function show(string $id): JsonResponse
    {
        $ticket = $this->ticketRepo->findById($id);

        if (! $ticket) {
            return response()->json(['message' => 'Not found.'], 404);
        }

        return response()->json($ticket);
    }

    public function assign(AssignTicketRequest $request, string $id): JsonResponse
    {
        $ticket = $this->assignUseCase->execute($id, $request->validated()['assignee_id']);

        return response()->json($ticket);
    }

    public function resolve(ResolveTicketRequest $request, string $id): JsonResponse
    {
        $validated = $request->validated();
        $ticket    = $this->resolveUseCase->execute(
            $id,
            $validated['resolver_id'],
            $validated['resolution_notes'] ?? null,
        );

        return response()->json($ticket);
    }

    public function close(string $id): JsonResponse
    {
        $ticket = $this->closeUseCase->execute($id);

        return response()->json($ticket);
    }

    public function destroy(string $id): JsonResponse
    {
        $this->ticketRepo->delete($id);

        return response()->json(null, 204);
    }
}
