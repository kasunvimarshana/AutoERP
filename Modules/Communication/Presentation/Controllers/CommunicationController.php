<?php

namespace Modules\Communication\Presentation\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Modules\Communication\Application\UseCases\CreateChannelUseCase;
use Modules\Communication\Application\UseCases\SendMessageUseCase;
use Modules\Communication\Domain\Contracts\ChannelRepositoryInterface;
use Modules\Communication\Domain\Contracts\MessageRepositoryInterface;
use Modules\Communication\Presentation\Requests\SendMessageRequest;
use Modules\Communication\Presentation\Requests\StoreChannelRequest;

class CommunicationController extends Controller
{
    public function __construct(
        private ChannelRepositoryInterface $channelRepo,
        private MessageRepositoryInterface $messageRepo,
        private CreateChannelUseCase       $createChannelUseCase,
        private SendMessageUseCase         $sendMessageUseCase,
    ) {}

    public function indexChannels(): JsonResponse
    {
        return response()->json($this->channelRepo->findByTenant(auth()->user()?->tenant_id));
    }

    public function storeChannel(StoreChannelRequest $request): JsonResponse
    {
        $channel = $this->createChannelUseCase->execute(array_merge(
            $request->validated(),
            [
                'tenant_id'  => auth()->user()?->tenant_id,
                'created_by' => auth()->id(),
            ]
        ));

        return response()->json($channel, 201);
    }

    public function showChannel(string $id): JsonResponse
    {
        $channel = $this->channelRepo->findById($id);

        if (! $channel) {
            return response()->json(['message' => 'Not found.'], 404);
        }

        return response()->json($channel);
    }

    public function destroyChannel(string $id): JsonResponse
    {
        $this->channelRepo->delete($id);

        return response()->json(null, 204);
    }

    public function indexMessages(string $channelId): JsonResponse
    {
        return response()->json($this->messageRepo->findByChannel($channelId));
    }

    public function sendMessage(SendMessageRequest $request, string $channelId): JsonResponse
    {
        $message = $this->sendMessageUseCase->execute(array_merge(
            $request->validated(),
            [
                'tenant_id'  => auth()->user()?->tenant_id,
                'channel_id' => $channelId,
                'sender_id'  => auth()->id(),
            ]
        ));

        return response()->json($message, 201);
    }
}
