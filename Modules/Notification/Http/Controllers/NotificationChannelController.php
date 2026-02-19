<?php

declare(strict_types=1);

namespace Modules\Notification\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Modules\Core\Http\Responses\ApiResponse;
use Modules\Notification\Http\Requests\StoreChannelRequest;
use Modules\Notification\Http\Requests\UpdateChannelRequest;
use Modules\Notification\Http\Resources\NotificationChannelResource;
use Modules\Notification\Models\NotificationChannel;
use Modules\Notification\Repositories\NotificationChannelRepository;

class NotificationChannelController extends Controller
{
    public function __construct(
        private NotificationChannelRepository $channelRepository
    ) {}

    /**
     * Display a listing of notification channels.
     */
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', NotificationChannel::class);

        $query = NotificationChannel::query();

        if ($request->has('type')) {
            $query->where('type', $request->type);
        }

        if ($request->has('driver')) {
            $query->where('driver', $request->driver);
        }

        if ($request->has('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        if ($request->has('is_default')) {
            $query->where('is_default', $request->boolean('is_default'));
        }

        if ($request->has('organization_id')) {
            $query->where('organization_id', $request->organization_id);
        }

        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('code', 'like', "%{$search}%");
            });
        }

        $perPage = $request->get('per_page', 15);
        $channels = $query->orderBy('priority')->paginate($perPage);

        return ApiResponse::paginated(
            $channels->setCollection(
                $channels->getCollection()->map(fn ($channel) => new NotificationChannelResource($channel))
            ),
            'Channels retrieved successfully'
        );
    }

    /**
     * Store a newly created notification channel.
     */
    public function store(StoreChannelRequest $request): JsonResponse
    {
        $data = $request->validated();
        $data['tenant_id'] = $request->user()->currentTenant()->id;

        $channel = DB::transaction(function () use ($data) {
            return $this->channelRepository->create($data);
        });

        return ApiResponse::created(
            new NotificationChannelResource($channel),
            'Channel created successfully'
        );
    }

    /**
     * Display the specified notification channel.
     */
    public function show(NotificationChannel $channel): JsonResponse
    {
        $this->authorize('view', $channel);

        return ApiResponse::success(
            new NotificationChannelResource($channel),
            'Channel retrieved successfully'
        );
    }

    /**
     * Update the specified notification channel.
     */
    public function update(UpdateChannelRequest $request, NotificationChannel $channel): JsonResponse
    {
        $data = $request->validated();

        $channel = DB::transaction(function () use ($channel, $data) {
            return $this->channelRepository->update($channel->id, $data);
        });

        return ApiResponse::success(
            new NotificationChannelResource($channel),
            'Channel updated successfully'
        );
    }

    /**
     * Remove the specified notification channel.
     */
    public function destroy(NotificationChannel $channel): JsonResponse
    {
        $this->authorize('delete', $channel);

        DB::transaction(function () use ($channel) {
            $this->channelRepository->delete($channel->id);
        });

        return ApiResponse::success(
            null,
            'Channel deleted successfully'
        );
    }

    /**
     * Activate the specified notification channel.
     */
    public function activate(NotificationChannel $channel): JsonResponse
    {
        $this->authorize('update', $channel);

        $channel = DB::transaction(function () use ($channel) {
            return $this->channelRepository->update($channel->id, ['is_active' => true]);
        });

        return ApiResponse::success(
            new NotificationChannelResource($channel),
            'Channel activated successfully'
        );
    }

    /**
     * Deactivate the specified notification channel.
     */
    public function deactivate(NotificationChannel $channel): JsonResponse
    {
        $this->authorize('update', $channel);

        $channel = DB::transaction(function () use ($channel) {
            return $this->channelRepository->update($channel->id, ['is_active' => false]);
        });

        return ApiResponse::success(
            new NotificationChannelResource($channel),
            'Channel deactivated successfully'
        );
    }
}
