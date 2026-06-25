<?php

declare(strict_types=1);

namespace Modules\Auth\Contracts\Providers;

use Modules\Auth\DTOs\TokenIssueData;
use Modules\Auth\DTOs\TokenRefreshData;

interface TokenProviderInterface
{
    /**
     * @return array<string, mixed>
     */
    public function issue(TokenIssueData $data): array;

    /**
     * @return array<string, mixed>|null
     */
    public function refresh(TokenRefreshData $data): ?array;

    /**
     * @return array<string, mixed>|null
     */
    public function validate(string $plainAccessToken, ?int $tenantId = null): ?array;

    public function revokeAccessToken(string $plainAccessToken, ?int $tenantId = null): bool;

    public function revokeSessionTokens(int $sessionId, ?int $tenantId = null): void;
}
