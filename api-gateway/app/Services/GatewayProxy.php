<?php

declare(strict_types=1);

namespace App\Services;

use App\Contracts\GatewayProxyInterface;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;

/**
 * Guzzle-based HTTP proxy implementation.
 *
 * Forwards the incoming request to a downstream service,
 * preserving headers (except hop-by-hop), query parameters,
 * and request body.
 *
 * Adds:
 *   - Correlation ID header (X-Request-ID) for distributed tracing
 *   - Timeout and connection-error handling
 *   - Structured request/response logging
 */
final class GatewayProxy implements GatewayProxyInterface
{
    private readonly Client $httpClient;

    public function __construct()
    {
        $this->httpClient = new Client([
            'timeout'         => 30,
            'connect_timeout' => 5,
            'http_errors'     => false, // Let us handle non-2xx responses
        ]);
    }

    /**
     * {@inheritDoc}
     */
    public function forward(string $serviceUrl, Request $request): Response
    {
        $path        = $request->getPathInfo();
        $queryString = $request->getQueryString();
        $targetUrl   = rtrim($serviceUrl, '/') . $path . ($queryString ? "?{$queryString}" : '');
        $requestId   = $request->header('X-Request-ID', (string) str()->uuid());

        $options = [
            'headers' => $this->buildForwardHeaders($request, $requestId),
            'body'    => $request->getContent() ?: null,
        ];

        Log::info("Gateway forwarding", [
            'request_id' => $requestId,
            'method'     => $request->method(),
            'target_url' => $targetUrl,
        ]);

        try {
            $response = $this->httpClient->request($request->method(), $targetUrl, $options);

            $statusCode   = $response->getStatusCode();
            $responseBody = $response->getBody()->getContents();
            $contentType  = $response->getHeaderLine('Content-Type') ?: 'application/json';

            Log::info("Gateway response", [
                'request_id'  => $requestId,
                'status_code' => $statusCode,
            ]);

            return response($responseBody, $statusCode)
                ->header('Content-Type', $contentType)
                ->header('X-Request-ID', $requestId);

        } catch (ConnectException $e) {
            Log::error("Gateway connection failed", [
                'request_id' => $requestId,
                'target_url' => $targetUrl,
                'error'      => $e->getMessage(),
            ]);

            return response(
                json_encode(['message' => 'Service unavailable.', 'request_id' => $requestId]),
                503
            )->header('Content-Type', 'application/json');

        } catch (RequestException $e) {
            Log::error("Gateway request failed", [
                'request_id' => $requestId,
                'error'      => $e->getMessage(),
            ]);

            return response(
                json_encode(['message' => 'Bad gateway.', 'request_id' => $requestId]),
                502
            )->header('Content-Type', 'application/json');
        }
    }

    // ──────────────────────────────────────────
    // Private helpers
    // ──────────────────────────────────────────

    /**
     * Build headers to forward, stripping hop-by-hop headers.
     *
     * @return array<string, string>
     */
    private function buildForwardHeaders(Request $request, string $requestId): array
    {
        // Hop-by-hop headers that must NOT be forwarded
        $hopByHop = [
            'connection', 'keep-alive', 'proxy-authenticate',
            'proxy-authorization', 'te', 'trailer', 'transfer-encoding', 'upgrade',
            'host', // Will be set by Guzzle
        ];

        $headers = [];

        foreach ($request->headers->all() as $name => $values) {
            if (!in_array(strtolower($name), $hopByHop, true)) {
                $headers[$name] = implode(', ', $values);
            }
        }

        // Inject tracing header
        $headers['X-Request-ID']       = $requestId;
        $headers['X-Forwarded-For']    = $request->ip();
        $headers['X-Forwarded-Proto']  = $request->scheme();
        $headers['Accept']             = 'application/json';

        return $headers;
    }
}
