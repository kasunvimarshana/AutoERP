<?php

namespace App\Http\Middleware;

use App\Models\IdempotencyKey;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

/**
 * HTTP Idempotency Middleware.
 *
 * When a client sends an `Idempotency-Key` header with a POST, PUT, or PATCH
 * request, this middleware:
 *
 *  1. Checks whether the key has already been used for the same user +
 *     method + path combination.
 *  2. If a **completed** response is cached, returns it immediately without
 *     re-executing the handler.
 *  3. If the record exists but has **not** been processed yet (concurrent
 *     in-flight request), returns HTTP 409 Conflict.
 *  4. If the key is **new**, creates the record, executes the handler, then
 *     stores the response for future replay.
 *
 * Keys expire after 24 hours by default (configurable via
 * `IDEMPOTENCY_TTL_HOURS` in `.env`).
 *
 * Requests without the `Idempotency-Key` header are passed through unchanged.
 */
class IdempotencyMiddleware
{
    /** Safe/idempotent HTTP methods that do not need this middleware. */
    private const PASS_THROUGH_METHODS = ['GET', 'HEAD', 'OPTIONS'];

    /** Maximum allowed byte length for a cached response body. */
    private const MAX_BODY_BYTES = 1_048_576; // 1 MiB

    public function handle(Request $request, Closure $next): Response
    {
        $key = $request->header('Idempotency-Key');

        // No header → pass through; GET/HEAD/OPTIONS → always pass through.
        if (! $key || in_array($request->method(), self::PASS_THROUGH_METHODS, true)) {
            return $next($request);
        }

        // Basic length validation.
        if (strlen($key) > 255) {
            return response()->json(
                ['message' => 'Idempotency-Key must not exceed 255 characters.'],
                422
            );
        }

        $userId = $request->user()?->id;
        $ttlHours = (int) env('IDEMPOTENCY_TTL_HOURS', 24);
        $expiresAt = now()->addHours($ttlHours);

        // Retrieve or create the record inside a transaction so two concurrent
        // requests with the same key cannot both slip through the "new" branch.
        $record = DB::transaction(function () use ($key, $userId, $request, $expiresAt) {
            $existing = IdempotencyKey::where('user_id', $userId)
                ->where('idempotency_key', $key)
                ->lockForUpdate()
                ->first();

            if ($existing) {
                return $existing;
            }

            return IdempotencyKey::create([
                'tenant_id' => $request->user()?->tenant_id,
                'user_id' => $userId,
                'idempotency_key' => $key,
                'request_method' => $request->method(),
                'request_path' => $request->path(),
                'processed_at' => null,
                'expires_at' => $expiresAt,
            ]);
        });

        // Expired record → delete and treat as a fresh key.
        if ($record->isExpired()) {
            $record->delete();

            $record = IdempotencyKey::create([
                'tenant_id' => $request->user()?->tenant_id,
                'user_id' => $userId,
                'idempotency_key' => $key,
                'request_method' => $request->method(),
                'request_path' => $request->path(),
                'processed_at' => null,
                'expires_at' => $expiresAt,
            ]);
        }

        // Already fully processed → replay the stored response.
        if ($record->isProcessed()) {
            return response(
                $record->response_body ?? '',
                $record->response_status ?? 200,
                [
                    'Content-Type' => 'application/json',
                    'Idempotency-Key' => $key,
                    'X-Idempotent-Replayed' => 'true',
                ]
            );
        }

        // In-flight concurrent duplicate → reject.
        if (! $record->wasRecentlyCreated) {
            return response()->json(
                ['message' => 'A request with this Idempotency-Key is already being processed.'],
                409
            );
        }

        // New key: execute the handler and store the result.
        /** @var Response $response */
        $response = $next($request);

        $body = $response->getContent();

        // Only cache if the body fits within the size limit.
        if (strlen((string) $body) <= self::MAX_BODY_BYTES) {
            $record->update([
                'response_status' => $response->getStatusCode(),
                'response_body' => $body,
                'processed_at' => now(),
            ]);
        } else {
            // Mark as processed without caching the body; subsequent replays
            // will receive an empty 200 (acceptable trade-off for huge payloads).
            $record->update([
                'response_status' => $response->getStatusCode(),
                'response_body' => null,
                'processed_at' => now(),
            ]);
        }

        $response->headers->set('Idempotency-Key', $key);

        return $response;
    }
}
