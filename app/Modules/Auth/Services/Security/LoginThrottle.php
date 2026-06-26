<?php

declare(strict_types=1);

namespace Modules\Auth\Services\Security;

use Illuminate\Cache\RateLimiter;

final readonly class LoginThrottle
{
    public function __construct(
        private RateLimiter $limiter,
        private AuthSecurityConfig $config,
    ) {}

    public function isBlocked(string $realm, string $identifier, string $ipAddress): bool
    {
        $keys = $this->keys($realm, $identifier, $ipAddress);

        return $this->limiter->tooManyAttempts($keys['account'], $this->config->accountMaxAttempts)
            || $this->limiter->tooManyAttempts($keys['account_ip'], $this->config->accountIpMaxAttempts)
            || $this->limiter->tooManyAttempts($keys['ip'], $this->config->globalIpMaxAttempts);
    }

    public function recordFailure(string $realm, string $identifier, string $ipAddress): void
    {
        foreach ($this->keys($realm, $identifier, $ipAddress) as $key) {
            $this->limiter->hit($key, $this->config->loginWindowSeconds);
        }
    }

    public function clearSuccessful(string $realm, string $identifier, string $ipAddress): void
    {
        foreach ($this->keys($realm, $identifier, $ipAddress) as $key) {
            $this->limiter->clear($key);
        }
    }

    /** @return array{account:string,account_ip:string,ip:string} */
    private function keys(string $realm, string $identifier, string $ipAddress): array
    {
        $realm = strtolower(trim($realm));
        $account = hash('sha256', strtolower(trim($identifier)));
        $network = hash('sha256', trim($ipAddress));

        return [
            'account' => "auth:{$realm}:account:{$account}",
            'account_ip' => "auth:{$realm}:account-ip:{$account}:{$network}",
            'ip' => "auth:{$realm}:ip:{$network}",
        ];
    }
}
