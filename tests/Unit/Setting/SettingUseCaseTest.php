<?php

namespace Tests\Unit\Setting;

use Illuminate\Container\Container;
use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Illuminate\Support\Facades\Cache;
use Mockery;
use Modules\Setting\Application\Services\SettingService;
use Modules\Setting\Application\UseCases\GetSettingGroupUseCase;
use Modules\Setting\Application\UseCases\GetSettingUseCase;
use Modules\Setting\Application\UseCases\UpdateSettingUseCase;
use Modules\Setting\Domain\Contracts\SettingRepositoryInterface;
use Modules\Setting\Domain\Enums\SettingGroup;
use Modules\Setting\Domain\Enums\SettingType;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for Setting module use cases and service.
 *
 * Verifies get, set, and getGroup flows including cache interactions and
 * delegation to the SettingRepositoryInterface.
 *
 * SettingService calls config() directly; we bind a stub config repository
 * in the Container so that call resolves without a full application boot.
 */
class SettingUseCaseTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        // config() helper calls app('config'); bind a stub so it resolves.
        $config = Mockery::mock(ConfigRepository::class);
        $config->shouldReceive('get')->andReturn(3600);
        Container::getInstance()->instance('config', $config);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    private function makeService(SettingRepositoryInterface $repo): SettingService
    {
        return new SettingService($repo);
    }

    // -------------------------------------------------------------------------
    // GetSettingUseCase
    // -------------------------------------------------------------------------

    public function test_get_setting_returns_cached_value(): void
    {
        $repo = Mockery::mock(SettingRepositoryInterface::class);
        $repo->shouldReceive('get')->never();

        Cache::shouldReceive('remember')
            ->once()
            ->andReturn('cached-value');

        $service = $this->makeService($repo);
        $useCase = new GetSettingUseCase($service);

        $result = $useCase->execute([
            'key'       => 'company_name',
            'tenant_id' => 'tenant-uuid-1',
            'default'   => null,
        ]);

        $this->assertSame('cached-value', $result);
    }

    public function test_get_setting_returns_default_when_cache_misses(): void
    {
        $repo = Mockery::mock(SettingRepositoryInterface::class);
        $repo->shouldReceive('get')
            ->once()
            ->with('missing_key', 'tenant-uuid-1', 'fallback')
            ->andReturn('fallback');

        Cache::shouldReceive('remember')
            ->once()
            ->andReturnUsing(fn ($key, $ttl, $cb) => $cb());

        $service = $this->makeService($repo);
        $useCase = new GetSettingUseCase($service);

        $result = $useCase->execute([
            'key'       => 'missing_key',
            'tenant_id' => 'tenant-uuid-1',
            'default'   => 'fallback',
        ]);

        $this->assertSame('fallback', $result);
    }

    // -------------------------------------------------------------------------
    // UpdateSettingUseCase
    // -------------------------------------------------------------------------

    public function test_update_setting_delegates_to_repo_and_clears_cache(): void
    {
        $repo = Mockery::mock(SettingRepositoryInterface::class);
        $repo->shouldReceive('set')
            ->once()
            ->with('company_name', 'Acme Corp', 'tenant-uuid-1', 'company', 'string');

        Cache::shouldReceive('forget')
            ->once()
            ->with('settings.tenant-uuid-1.company_name');
        Cache::shouldReceive('forget')
            ->once()
            ->with('settings.tenant-uuid-1.group.company');

        $service = $this->makeService($repo);
        $useCase = new UpdateSettingUseCase($service);

        $useCase->execute([
            'key'       => 'company_name',
            'value'     => 'Acme Corp',
            'tenant_id' => 'tenant-uuid-1',
            'group'     => 'company',
            'type'      => 'string',
        ]);

        $this->assertTrue(true);
    }

    public function test_update_setting_uses_string_type_by_default(): void
    {
        $repo = Mockery::mock(SettingRepositoryInterface::class);
        $repo->shouldReceive('set')
            ->once()
            ->withArgs(fn ($k, $v, $t, $g, $type) => $type === 'string');

        Cache::shouldReceive('forget')->twice();

        $service = $this->makeService($repo);
        $useCase = new UpdateSettingUseCase($service);

        $useCase->execute([
            'key'       => 'some_flag',
            'value'     => 'on',
            'tenant_id' => 'tenant-uuid-1',
            'group'     => 'company',
        ]);

        $this->assertTrue(true);
    }

    // -------------------------------------------------------------------------
    // GetSettingGroupUseCase
    // -------------------------------------------------------------------------

    public function test_get_setting_group_returns_all_settings_in_group(): void
    {
        $groupSettings = [
            'company_name'  => 'Acme Corp',
            'company_email' => 'info@acme.com',
        ];

        $repo = Mockery::mock(SettingRepositoryInterface::class);
        $repo->shouldReceive('getGroup')->never();

        Cache::shouldReceive('remember')
            ->once()
            ->andReturn($groupSettings);

        $service = $this->makeService($repo);
        $useCase = new GetSettingGroupUseCase($service);

        $result = $useCase->execute([
            'group'     => 'company',
            'tenant_id' => 'tenant-uuid-1',
        ]);

        $this->assertSame($groupSettings, $result);
        $this->assertCount(2, $result);
    }

    public function test_get_setting_group_falls_through_to_repo_on_cache_miss(): void
    {
        $groupSettings = ['finance_currency' => 'USD'];

        $repo = Mockery::mock(SettingRepositoryInterface::class);
        $repo->shouldReceive('getGroup')
            ->once()
            ->with('finance', 'tenant-uuid-1')
            ->andReturn($groupSettings);

        Cache::shouldReceive('remember')
            ->once()
            ->andReturnUsing(fn ($key, $ttl, $cb) => $cb());

        $service = $this->makeService($repo);
        $useCase = new GetSettingGroupUseCase($service);

        $result = $useCase->execute([
            'group'     => 'finance',
            'tenant_id' => 'tenant-uuid-1',
        ]);

        $this->assertSame($groupSettings, $result);
    }

    // -------------------------------------------------------------------------
    // SettingGroup enum
    // -------------------------------------------------------------------------

    public function test_setting_group_enum_has_expected_cases(): void
    {
        $cases = array_map(fn ($c) => $c->value, SettingGroup::cases());

        $this->assertContains('company', $cases);
        $this->assertContains('finance', $cases);
        $this->assertContains('inventory', $cases);
        $this->assertContains('sales', $cases);
        $this->assertContains('hr', $cases);
        $this->assertContains('notification', $cases);
        $this->assertContains('integration', $cases);
    }

    // -------------------------------------------------------------------------
    // SettingType enum
    // -------------------------------------------------------------------------

    public function test_setting_type_enum_has_expected_cases(): void
    {
        $cases = array_map(fn ($c) => $c->value, SettingType::cases());

        $this->assertContains('string', $cases);
        $this->assertContains('integer', $cases);
        $this->assertContains('boolean', $cases);
        $this->assertContains('json', $cases);
        $this->assertContains('enum', $cases);
    }
}
