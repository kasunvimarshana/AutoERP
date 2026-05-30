<?php

declare(strict_types=1);

namespace Tests\Unit\Modules\Auth;

use Modules\Auth\Application\Contracts\Providers\AuthProviderRegistryInterface;
use Modules\Auth\Application\Contracts\Providers\AuthenticationProviderInterface;
use Modules\Auth\Application\DTOs\LoginData;
use Modules\Auth\Application\Repositories\AuthIdentityRepositoryInterface;
use Modules\Auth\Application\Repositories\AuthLoginAttemptRepositoryInterface;
use Modules\Auth\Application\Repositories\AuthProviderRepositoryInterface;
use Modules\Auth\Application\UseCases\AuthWorkflowService;
use Modules\Auth\Domain\Contracts\AuthDomainServiceInterface;
use Modules\Core\Application\Contracts\ErrorNormalizerInterface;
use Modules\Core\Application\Contracts\TransactionManagerInterface;
use Modules\Core\Application\DTO\DataRecord;
use Modules\User\Application\Contracts\UseCases\UserServiceInterface;
use PHPUnit\Framework\TestCase;

final class AuthWorkflowLoginTest extends TestCase
{
    public function testItRejectsInvalidCredentialsAndRecordsLoginAttempt(): void
    {
        $registry = $this->createMock(AuthProviderRegistryInterface::class);
        $provider = $this->createMock(AuthenticationProviderInterface::class);
        $providers = $this->createMock(AuthProviderRepositoryInterface::class);
        $identities = $this->createMock(AuthIdentityRepositoryInterface::class);
        $loginAttempts = $this->createMock(AuthLoginAttemptRepositoryInterface::class);
        $domain = $this->createMock(AuthDomainServiceInterface::class);
        $userService = $this->createMock(UserServiceInterface::class);
        $transactions = $this->createMock(TransactionManagerInterface::class);
        $errorNormalizer = $this->createMock(ErrorNormalizerInterface::class);

        $transactions->method('runInTransaction')->willReturnCallback(static fn (callable $callback): mixed => $callback());
        $registry->method('authenticationProvider')->willReturn($provider);
        $provider->method('authenticate')->willReturn(null);
        $domain->method('normalizeMetadata')->willReturn(null);

        $loginAttempts->method('countRecentFailures')->willReturn(0);

        $loginAttempts->expects(self::once())
            ->method('create')
            ->willReturn(new DataRecord(['id' => 1]));

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

        $result = $service->login(LoginData::fromArray([
            'tenant_id' => 1,
            'provider_key' => 'internal',
            'login_identifier' => 'jane@example.com',
            'password' => 'wrong-password',
        ]));

        self::assertTrue($result->isFailure());
        self::assertSame('AUTH_INVALID_CREDENTIALS', $result->errorOrFail()->code);
    }
}
