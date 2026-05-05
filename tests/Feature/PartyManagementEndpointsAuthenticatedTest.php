<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Validation\PresenceVerifierInterface;
use Modules\PartyManagement\Application\Contracts\ManageAssetOwnershipServiceInterface;
use Modules\PartyManagement\Application\Contracts\ManagePartyServiceInterface;
use Modules\PartyManagement\Domain\Entities\Party;
use Modules\Tenant\Application\Contracts\TenantConfigClientInterface;
use Modules\Tenant\Application\Contracts\TenantConfigManagerInterface;
use Modules\User\Infrastructure\Persistence\Eloquent\Models\UserModel;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\HttpFoundation\Response as HttpResponse;
use Tests\TestCase;

class PartyManagementEndpointsAuthenticatedTest extends TestCase
{
    private static bool $routesCleared = false;

    /** @var ManagePartyServiceInterface&MockObject */
    private ManagePartyServiceInterface $managePartyService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->clearRoutesCacheOnce();

        $this->managePartyService = $this->createMock(ManagePartyServiceInterface::class);

        $this->app->instance(ManagePartyServiceInterface::class, $this->managePartyService);
        $this->app->instance(ManageAssetOwnershipServiceInterface::class, $this->createMock(ManageAssetOwnershipServiceInterface::class));

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
            'id' => 510,
            'tenant_id' => 7,
            'email' => 'party.test@example.com',
            'password' => 'secret',
            'first_name' => 'Party',
            'last_name' => 'Tester',
        ]);
        $user->setAttribute('id', 510);
        $user->setAttribute('tenant_id', 7);

        $this->actingAs($user, (string) config('auth_context.guards.api', config('auth.defaults.guard', 'api')));
    }

    public function test_authenticated_party_index_returns_success_payload(): void
    {
        $this->managePartyService
            ->expects($this->once())
            ->method('list')
            ->with(7)
            ->willReturn(['data' => [$this->buildParty('party-uuid-1')], 'total' => 1, 'page' => 1, 'per_page' => 15]);

        $response = $this->withHeader('X-Tenant-ID', '7')
            ->getJson('/api/parties');

        $response->assertStatus(HttpResponse::HTTP_OK);
    }

    public function test_authenticated_party_show_returns_success_payload(): void
    {
        $party = $this->buildParty('party-uuid-1');

        $this->managePartyService
            ->expects($this->once())
            ->method('find')
            ->with(7, 'party-uuid-1')
            ->willReturn($party);

        $response = $this->withHeader('X-Tenant-ID', '7')
            ->getJson('/api/parties/party-uuid-1');

        $response->assertStatus(HttpResponse::HTTP_OK)
            ->assertJsonPath('id', 'party-uuid-1')
            ->assertJsonPath('name', 'Acme Corporation');
    }

    public function test_authenticated_party_store_validates_required_fields(): void
    {
        $this->managePartyService
            ->expects($this->never())
            ->method('create');

        $response = $this->withHeader('X-Tenant-ID', '7')
            ->postJson('/api/parties', []);

        $response->assertStatus(HttpResponse::HTTP_UNPROCESSABLE_ENTITY)
            ->assertJsonValidationErrors(['party_type', 'name']);
    }

    private function buildParty(string $id): Party
    {
        return new Party(
            id: $id,
            tenantId: 7,
            partyType: 'company',
            name: 'Acme Corporation',
            taxNumber: null,
            email: 'contact@acme.com',
            phone: '+1234567890',
            addressLine1: '123 Main St',
            addressLine2: null,
            city: 'Metropolis',
            stateProvince: 'NY',
            postalCode: '10001',
            countryCode: 'US',
            isActive: true,
            notes: null,
        );
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
