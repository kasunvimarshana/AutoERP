<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Validation\PresenceVerifierInterface;
use Modules\ReturnRefund\Application\Contracts\ProcessReturnAndRefundServiceInterface;
use Modules\Tenant\Application\Contracts\TenantConfigClientInterface;
use Modules\Tenant\Application\Contracts\TenantConfigManagerInterface;
use Modules\User\Infrastructure\Persistence\Eloquent\Models\UserModel;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\HttpFoundation\Response as HttpResponse;
use Tests\TestCase;

class ReturnRefundEndpointsAuthenticatedTest extends TestCase
{
    private static bool $routesCleared = false;

    /** @var ProcessReturnAndRefundServiceInterface&MockObject */
    private ProcessReturnAndRefundServiceInterface $processService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->clearRoutesCacheOnce();

        $this->processService = $this->createMock(ProcessReturnAndRefundServiceInterface::class);
        $this->app->instance(ProcessReturnAndRefundServiceInterface::class, $this->processService);

        $tenantConfigClient = $this->createMock(TenantConfigClientInterface::class);
        $tenantConfigClient->method('getConfig')->willReturn(null);
        $this->app->instance(TenantConfigClientInterface::class, $tenantConfigClient);

        $tenantConfigManager = $this->createMock(TenantConfigManagerInterface::class);
        $this->app->instance(TenantConfigManagerInterface::class, $tenantConfigManager);

        $presenceVerifier = $this->createMock(PresenceVerifierInterface::class);
        $presenceVerifier->method('getCount')->willReturn(0);
        $presenceVerifier->method('getMultiCount')->willReturn(1);
        $this->app->instance(PresenceVerifierInterface::class, $presenceVerifier);
        $this->app['validator']->setPresenceVerifier($presenceVerifier);

        $user = new UserModel([
            'id' => 506,
            'tenant_id' => 7,
            'email' => 'return.test@example.com',
            'password' => 'secret',
            'first_name' => 'Return',
            'last_name' => 'Tester',
        ]);
        $user->setAttribute('id', 506);
        $user->setAttribute('tenant_id', 7);

        $this->actingAs($user, (string) config('auth_context.guards.api', config('auth.defaults.guard', 'api')));
    }

    public function test_authenticated_process_return_refund_validates_required_fields(): void
    {
        $this->processService
            ->expects($this->never())
            ->method('execute');

        $response = $this->withHeader('X-Tenant-ID', '7')
            ->postJson('/api/return-refund/process', []);

        $response->assertStatus(HttpResponse::HTTP_UNPROCESSABLE_ENTITY)
            ->assertJsonValidationErrors(['rental_transaction_id', 'gross_amount']);
    }

    private function clearRoutesCacheOnce(): void
    {
        if (self::$routesCleared) {
            return;
        }

        Artisan::call('route:clear');
        self::$routesCleared = true;
    }
}
