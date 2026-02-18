<?php

namespace Modules\Core\Services;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class LoggingService
{
    protected TenantContext $tenantContext;

    public function __construct(TenantContext $tenantContext)
    {
        $this->tenantContext = $tenantContext;
    }

    protected function addContext(array $context = []): array
    {
        return array_merge([
            'tenant_id' => $this->tenantContext->getTenantId(),
            'user_id' => Auth::id(),
            'timestamp' => now()->toIso8601String(),
        ], $context);
    }

    public function emergency(string $message, array $context = []): void
    {
        Log::emergency($message, $this->addContext($context));
    }

    public function alert(string $message, array $context = []): void
    {
        Log::alert($message, $this->addContext($context));
    }

    public function critical(string $message, array $context = []): void
    {
        Log::critical($message, $this->addContext($context));
    }

    public function error(string $message, array $context = []): void
    {
        Log::error($message, $this->addContext($context));
    }

    public function warning(string $message, array $context = []): void
    {
        Log::warning($message, $this->addContext($context));
    }

    public function notice(string $message, array $context = []): void
    {
        Log::notice($message, $this->addContext($context));
    }

    public function info(string $message, array $context = []): void
    {
        Log::info($message, $this->addContext($context));
    }

    public function debug(string $message, array $context = []): void
    {
        Log::debug($message, $this->addContext($context));
    }

    public function log(string $level, string $message, array $context = []): void
    {
        Log::log($level, $message, $this->addContext($context));
    }
}
