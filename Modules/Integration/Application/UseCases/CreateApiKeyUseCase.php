<?php

namespace Modules\Integration\Application\UseCases;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Str;
use Modules\Integration\Domain\Contracts\ApiKeyRepositoryInterface;
use Modules\Integration\Domain\Events\ApiKeyCreated;

class CreateApiKeyUseCase
{
    public function __construct(
        private ApiKeyRepositoryInterface $apiKeyRepo,
    ) {}

    public function execute(array $data): object
    {
        return DB::transaction(function () use ($data) {
            $rawKey = 'kv_' . Str::random(40);

            $apiKey = $this->apiKeyRepo->create([
                'tenant_id'  => $data['tenant_id'],
                'name'       => $data['name'],
                'key_hash'   => hash('sha256', $rawKey),
                'key_prefix' => substr($rawKey, 0, 8),
                'scopes'     => $data['scopes'] ?? [],
                'expires_at' => $data['expires_at'] ?? null,
                'is_active'  => true,
            ]);

            Event::dispatch(new ApiKeyCreated(
                $apiKey->id,
                $apiKey->tenant_id,
                $apiKey->name,
            ));

            // Return the plaintext key only once at creation time
            $apiKeyArray = method_exists($apiKey, 'toArray') ? $apiKey->toArray() : (array) $apiKey;

            return (object) array_merge($apiKeyArray, ['plain_key' => $rawKey]);
        });
    }
}
