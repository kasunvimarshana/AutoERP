<?php

declare(strict_types=1);

namespace Tests\Unit\Modules\Auth;

use Modules\Auth\Application\Contracts\Providers\AuthProviderRegistryInterface;
use Modules\Auth\Application\Contracts\Providers\SsoProviderInterface;
use Modules\Auth\Application\DTOs\AuthorizeClientData;
use Modules\Auth\Application\Repositories\AuthIdentityRepositoryInterface;
use Modules\Auth\Application\Repositories\AuthLoginAttemptRepositoryInterface;
use Modules\Auth\Application\Repositories\AuthProviderRepositoryInterface;
use Modules\Auth\Application\UseCases\AuthWorkflowService;
use Modules\Auth\Domain\Contracts\AuthDomainServiceInterface;
use Modules\Core\Application\Contracts\ErrorNormalizerInterface;
use Modules\Core\Application\Contracts\TransactionManagerInterface;
use Modules\User\Application\Contracts\UseCases\UserServiceInterface;
use PHPUnit\Framework\TestCase;

final class AuthWorkflowAuthorizeClientTest extends TestCase
{
    public function testItReturnsFailureWhenClientCannotBeAuthorized(): void
    {
        $registry = $this->createMock(AuthProviderRegistryInterface::class);
        $providers = $this->createMock(AuthProviderRepositoryInterface::class);
        $identities = $this->createMock(AuthIdentityRepositoryInterface::class);
        $loginAttempts = $this->createMock(AuthLoginAttemptRepositoryInterface::class);
        $domain = $this->createMock(AuthDomainServiceInterface::class);
        $userService = $this->createMock(UserServiceInterface::class);
        $transactions = $this->createMock(TransactionManagerInterface::class);
        $errorNormalizer = $this->createMock(ErrorNormalizerInterface::class);
        $ssoProvider = $this->createMock(SsoProviderInterface::class);

        $transactions->method('runInTransaction')->willReturnCallback(static fn (callable $callback): mixed => $callback());
        $registry->method('ssoProvider')->willReturn($ssoProvider);
        $ssoProvider->method('authorizeClient')->willReturn(null);

        $service = new AuthWorkflowService(
            $registry,
            $providers,
            $identities,
            $loginAttempts,
            $domain,
            $userService,
            $transactions,
            $errorNormalizer,
        );

        $result = $service->authorizeClient(AuthorizeClientData::fromArray([
            'tenant_id' => 1,
            'client_key' => 'erp_web',
            'client_secret' => 'invalid',
            'scopes' => ['profile'],
        ]));

        self::assertTrue($result->isFailure());
        self::assertSame('AUTH_CLIENT_NOT_ALLOWED', $result->errorOrFail()->code);
    }
}
