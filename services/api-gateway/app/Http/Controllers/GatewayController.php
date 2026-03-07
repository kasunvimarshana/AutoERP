<?php

namespace App\Http\Controllers;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Request as GuzzleRequest;
use GuzzleHttp\Psr7\Response as GuzzleResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Psr\Http\Message\ResponseInterface;

class GatewayController extends Controller
{
    /** Services that may never be forwarded to (security deny-list). */
    private const BLOCKED_SERVICES = [];

    /** Maximum number of retry attempts for transient errors. */
    private const MAX_RETRIES = 2;

    /** Seconds to wait for an upstream response before timing out. */
    private const TIMEOUT_SECONDS = 10;

    /** Headers that must not be forwarded to upstream services. */
    private const STRIPPED_REQUEST_HEADERS = [
        'host',
        'connection',
        'content-length',
    ];

    // -------------------------------------------------------------------------
    // Entry-point
    // -------------------------------------------------------------------------

    /**
     * Forward the incoming HTTP request to the appropriate microservice,
     * enrich it with tenant context, and stream the response back.
     *
     * Route: ANY /v1/{service}/{path?}
     */
    public function proxy(Request $request, string $service, string $path = ''): \Illuminate\Http\Response|JsonResponse
    {
        if (in_array($service, self::BLOCKED_SERVICES, true)) {
            return response()->json(['message' => 'Service not accessible.'], 403);
        }

        $serviceUrl = $this->resolveServiceUrl($service);

        if ($serviceUrl === null) {
            return response()->json([
                'message' => "Unknown service: {$service}.",
            ], 404);
        }

        $targetUrl = rtrim($serviceUrl, '/') . '/' . ltrim($path, '/');

        if ($request->getQueryString()) {
            $targetUrl .= '?' . $request->getQueryString();
        }

        try {
            $guzzle   = $this->buildClient();
            $upstream = $guzzle->request(
                $request->method(),
                $targetUrl,
                $this->buildOptions($request)
            );

            return $this->buildResponse($upstream);
        } catch (ConnectException $e) {
            Log::error('[Gateway] Connection failed', [
                'service' => $service,
                'url'     => $targetUrl,
                'error'   => $e->getMessage(),
            ]);

            return response()->json([
                'message' => 'Service temporarily unavailable. Please try again later.',
            ], 503);
        } catch (RequestException $e) {
            if ($e->hasResponse()) {
                return $this->buildResponse($e->getResponse());
            }

            Log::error('[Gateway] Request failed', [
                'service' => $service,
                'url'     => $targetUrl,
                'error'   => $e->getMessage(),
            ]);

            return response()->json(['message' => 'Bad gateway.'], 502);
        }
    }

    // -------------------------------------------------------------------------
    // Private helpers
    // -------------------------------------------------------------------------

    /**
     * Resolve the upstream base URL for the given logical service name.
     */
    private function resolveServiceUrl(string $service): ?string
    {
        $map = [
            'orders'       => config('services.order_service.url'),
            'inventory'    => config('services.inventory_service.url'),
            'payments'     => config('services.payment_service.url'),
            'notifications' => config('services.notification_service.url'),
        ];

        return $map[$service] ?? null;
    }

    /**
     * Build a Guzzle client with a retry middleware for transient failures.
     */
    private function buildClient(): Client
    {
        $stack = HandlerStack::create();
        $stack->push($this->retryMiddleware());

        return new Client([
            'handler'         => $stack,
            'timeout'         => self::TIMEOUT_SECONDS,
            'connect_timeout' => 5,
            'http_errors'     => false,
        ]);
    }

    /**
     * Build the Guzzle request options from the Laravel request.
     */
    private function buildOptions(Request $request): array
    {
        $headers = collect($request->headers->all())
            ->mapWithKeys(fn ($value, $key) => [$key => $value[0]])
            ->reject(fn ($_, $key) => in_array(strtolower($key), self::STRIPPED_REQUEST_HEADERS, true))
            ->all();

        // Inject tenant context so microservices can enforce isolation.
        if ($tenantId = $request->attributes->get('tenant_id')) {
            $headers['X-Tenant-ID'] = (string) $tenantId;
        }

        if ($user = $request->user()) {
            $headers['X-User-ID']   = (string) $user->id;
            $headers['X-User-Role'] = (string) $user->role;
        }

        $options = [
            'headers' => $headers,
        ];

        $contentType = strtolower($request->header('Content-Type', ''));

        if (str_contains($contentType, 'multipart/form-data')) {
            $options['multipart'] = $this->buildMultipart($request);
        } elseif ($request->isJson()) {
            $options['json'] = $request->json()->all();
        } elseif ($request->getContent()) {
            $options['body'] = $request->getContent();
        }

        return $options;
    }

    /**
     * Build a multipart payload from uploaded files + form fields.
     *
     * @return list<array{name: string, contents: mixed, filename?: string}>
     */
    private function buildMultipart(Request $request): array
    {
        $parts = [];

        foreach ($request->all() as $key => $value) {
            $parts[] = ['name' => $key, 'contents' => (string) $value];
        }

        foreach ($request->allFiles() as $key => $file) {
            $parts[] = [
                'name'     => $key,
                'contents' => fopen($file->getRealPath(), 'rb'),
                'filename' => $file->getClientOriginalName(),
            ];
        }

        return $parts;
    }

    /**
     * Convert a Guzzle PSR-7 response into a Laravel response.
     */
    private function buildResponse(ResponseInterface $upstream): \Illuminate\Http\Response
    {
        $headers = [];
        foreach ($upstream->getHeaders() as $name => $values) {
            $headers[$name] = implode(', ', $values);
        }

        // Drop hop-by-hop headers that must not be forwarded.
        unset($headers['Transfer-Encoding'], $headers['Connection']);

        return response(
            $upstream->getBody()->getContents(),
            $upstream->getStatusCode(),
            $headers
        );
    }

    /**
     * Build a retry middleware that retries on connection errors and 5xx responses.
     */
    private function retryMiddleware(): callable
    {
        return Middleware::retry(
            function (
                int $retries,
                GuzzleRequest $_request,
                ?GuzzleResponse $response,
                ?\Throwable $exception
            ) {
                if ($retries >= self::MAX_RETRIES) {
                    return false;
                }

                if ($exception instanceof ConnectException) {
                    return true;
                }

                if ($response && $response->getStatusCode() >= 500) {
                    return true;
                }

                return false;
            },
            fn (int $retries) => $retries * 200   // back-off: 200 ms, 400 ms …
        );
    }
}
