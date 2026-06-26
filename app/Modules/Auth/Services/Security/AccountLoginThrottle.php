<?php

declare(strict_types=1);

namespace Modules\Auth\Services\Security;

use Illuminate\Cache\RateLimiter;

/**
 * Account-aware throttling. Route middleware owns the coarse IP-wide limit.
 */
final readonly class AccountLoginThrottle
{
    public function __construct(
        private RateLimiter $limiter,
        private AuthSecurityConfig $config,
    ) {}

    public function isBlocked(string $realm, string $identifier, string $ipAddress): bool
    {
        $keys = $this->keys($realm, $identifier, $ipAddress);

        return $this->limiter->tooManyAttempts($keys['account'], $this->config->accountMaxAttempts)
            || $this->limiter->tooManyAttempts($keys['account_ip'], $this->config->accountIpMaxAttempts);
    }

    public function recordFailure(string $realm, string $identifier, string $ipAddress): void
    {
        $keys = $this->keys($realm, $identifier, $ipAddress);
        $this->limiter->hit($keys['account'], $this->config->loginWindowSeconds);
        $this->limiter->hit($keys['account_ip'], $this->config->loginWindowSeconds);
    }

    public function clearSuccessful(string $realm, string $identifier, string $ipAddress): void
    {
        $keys = $this->keys($realm, $identifier, $ipAddress);
        $this->limiter->clear($keys['account']);
        $this->limiter->clear($keys['account_ip']);
    }

    /** @return array{account:string,account_ip:string} */
    private function keys(string $realm, string $identifier, string $ipAddress): array
    {
        $realm = strtolower(trim($realm));
        $account = hash('sha256', strtolower(trim($identifier)));
        $network = hash('sha256', trim($ipAddress));

        return [
            'account' => "auth:{$realm}:account:{$account}",
            'account_ip' => "auth:{$realm}:account-ip:{$account}:{$network}",
        ];
    }
}
