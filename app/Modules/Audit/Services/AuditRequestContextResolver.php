<?php

declare(strict_types=1);

namespace Modules\Audit\Services;

use Illuminate\Http\Request;
use Illuminate\Support\Str;

final class AuditRequestContextResolver
{
    private ?string $generatedRequestId = null;

    public function __construct(private readonly Request $request) {}

    /** @return array<string, string|null> */
    public function resolve(): array
    {
        $route = $this->request->route();
        $routeName = is_object($route) && method_exists($route, 'getName') ? $route->getName() : null;
        $headerName = (string) config('audit.request_id_header', 'X-Request-ID');
        $requestId = trim((string) $this->request->header($headerName, ''));

        if ($requestId === '') {
            $this->generatedRequestId ??= (string) Str::uuid();
            $requestId = $this->generatedRequestId;
        }

        return [
            'request_id' => mb_substr($requestId, 0, 100),
            'request_method' => mb_substr(strtoupper($this->request->method()), 0, 12),
            'route_name' => is_string($routeName) && trim($routeName) !== '' ? mb_substr($routeName, 0, 255) : null,
            'route_path' => '/'.ltrim(mb_substr($this->request->path(), 0, 254), '/'),
            'ip_address' => ($ip = $this->request->ip()) !== null ? mb_substr($ip, 0, 45) : null,
            'user_agent' => ($agent = $this->request->userAgent()) !== null ? mb_substr($agent, 0, 500) : null,
        ];
    }
}
