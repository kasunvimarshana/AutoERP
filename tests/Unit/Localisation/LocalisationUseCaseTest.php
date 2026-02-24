<?php

namespace Tests\Unit\Localisation;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Mockery;
use Modules\Localisation\Application\UseCases\CreateLanguagePackUseCase;
use Modules\Localisation\Application\UseCases\UpdateLocalePreferenceUseCase;
use Modules\Localisation\Domain\Contracts\LanguagePackRepositoryInterface;
use Modules\Localisation\Domain\Contracts\LocalePreferenceRepositoryInterface;
use Modules\Localisation\Domain\Events\LanguagePackCreated;
use Modules\Localisation\Domain\Events\LocalePreferenceUpdated;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for Localisation module use cases.
 *
 * Covers language pack creation and locale preference upsert.
 */
class LocalisationUseCaseTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    // -------------------------------------------------------------------------
    // CreateLanguagePackUseCase
    // -------------------------------------------------------------------------

    public function test_create_language_pack_dispatches_event(): void
    {
        $pack = (object) [
            'id'        => 'pack-uuid-1',
            'tenant_id' => 'tenant-uuid-1',
            'locale'    => 'ar',
            'name'      => 'Arabic',
            'direction' => 'rtl',
            'is_active' => true,
        ];

        $repo = Mockery::mock(LanguagePackRepositoryInterface::class);
        $repo->shouldReceive('create')
            ->once()
            ->withArgs(fn ($data) => $data['locale'] === 'ar' && $data['direction'] === 'rtl' && $data['is_active'] === true)
            ->andReturn($pack);

        DB::shouldReceive('transaction')->andReturnUsing(fn ($cb) => $cb());
        Event::shouldReceive('dispatch')
            ->once()
            ->withArgs(fn ($e) => $e instanceof LanguagePackCreated && $e->locale === 'ar');

        $useCase = new CreateLanguagePackUseCase($repo);
        $result  = $useCase->execute([
            'tenant_id' => 'tenant-uuid-1',
            'locale'    => 'ar',
            'name'      => 'Arabic',
            'direction' => 'rtl',
        ]);

        $this->assertSame('rtl', $result->direction);
    }

    public function test_create_language_pack_defaults_to_ltr(): void
    {
        $pack = (object) [
            'id'        => 'pack-uuid-2',
            'tenant_id' => 'tenant-uuid-1',
            'locale'    => 'en',
            'name'      => 'English',
            'direction' => 'ltr',
            'is_active' => true,
        ];

        $repo = Mockery::mock(LanguagePackRepositoryInterface::class);
        $repo->shouldReceive('create')
            ->once()
            ->withArgs(fn ($data) => $data['direction'] === 'ltr')
            ->andReturn($pack);

        DB::shouldReceive('transaction')->andReturnUsing(fn ($cb) => $cb());
        Event::shouldReceive('dispatch')->once();

        $useCase = new CreateLanguagePackUseCase($repo);
        $result  = $useCase->execute([
            'tenant_id' => 'tenant-uuid-1',
            'locale'    => 'en',
            'name'      => 'English',
        ]);

        $this->assertSame('ltr', $result->direction);
    }

    // -------------------------------------------------------------------------
    // UpdateLocalePreferenceUseCase
    // -------------------------------------------------------------------------

    public function test_update_locale_preference_upserts_and_dispatches_event(): void
    {
        $preference = (object) [
            'user_id'   => 'user-uuid-1',
            'tenant_id' => 'tenant-uuid-1',
            'locale'    => 'fr',
            'timezone'  => 'Europe/Paris',
        ];

        $repo = Mockery::mock(LocalePreferenceRepositoryInterface::class);
        $repo->shouldReceive('upsert')
            ->once()
            ->withArgs(fn ($data) => $data['locale'] === 'fr' && $data['timezone'] === 'Europe/Paris')
            ->andReturn($preference);

        DB::shouldReceive('transaction')->andReturnUsing(fn ($cb) => $cb());
        Event::shouldReceive('dispatch')
            ->once()
            ->withArgs(fn ($e) => $e instanceof LocalePreferenceUpdated
                && $e->locale === 'fr'
                && $e->timezone === 'Europe/Paris');

        $useCase = new UpdateLocalePreferenceUseCase($repo);
        $result  = $useCase->execute([
            'user_id'   => 'user-uuid-1',
            'tenant_id' => 'tenant-uuid-1',
            'locale'    => 'fr',
            'timezone'  => 'Europe/Paris',
        ]);

        $this->assertSame('fr', $result->locale);
    }
}
