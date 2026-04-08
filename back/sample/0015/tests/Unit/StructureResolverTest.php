<?php

namespace YourVendor\LaravelDDDArchitect\Tests\Unit;

use YourVendor\LaravelDDDArchitect\Support\StructureResolver;
use YourVendor\LaravelDDDArchitect\Tests\TestCase;

class StructureResolverTest extends TestCase
{
    private array $baseConfig;

    protected function setUp(): void
    {
        parent::setUp();

        $this->baseConfig = [
            'structure'  => 'ddd-layered',
            'base_path'  => 'app',
            'namespace'  => 'App',
            'mode'       => 'full',
            'structure_choices' => [
                'ddd-layered' => [
                    'base_path' => 'app',
                    'namespace' => 'App',
                ],
                'ddd-modular' => [
                    'base_path' => 'src',
                    'namespace' => 'Src',
                    'mode'      => 'full',
                    'domain_structure' => ['Domain/Entities', 'Domain/ValueObjects'],
                ],
                'custom' => [],
            ],
        ];
    }

    /** @test */
    public function it_returns_base_config_for_unknown_preset(): void
    {
        $config = $this->baseConfig;
        $config['structure'] = 'non-existent';

        $resolved = StructureResolver::resolve($config);

        $this->assertSame('non-existent', $resolved['structure']);
        $this->assertSame('app', $resolved['base_path']);
    }

    /** @test */
    public function it_merges_ddd_layered_preset_over_root_config(): void
    {
        $resolved = StructureResolver::resolve($this->baseConfig);

        $this->assertSame('app', $resolved['base_path']);
        $this->assertSame('App', $resolved['namespace']);
    }

    /** @test */
    public function it_merges_ddd_modular_preset_overriding_base_path_and_namespace(): void
    {
        $config = $this->baseConfig;
        $config['structure'] = 'ddd-modular';

        $resolved = StructureResolver::resolve($config);

        $this->assertSame('src', $resolved['base_path']);
        $this->assertSame('Src', $resolved['namespace']);
        $this->assertSame(['Domain/Entities', 'Domain/ValueObjects'], $resolved['domain_structure']);
    }

    /** @test */
    public function it_returns_base_config_for_empty_custom_preset(): void
    {
        $config = $this->baseConfig;
        $config['structure'] = 'custom';

        $resolved = StructureResolver::resolve($config);

        // custom preset is empty — root config should be unchanged
        $this->assertSame('app', $resolved['base_path']);
    }

    /** @test */
    public function it_lists_all_available_presets(): void
    {
        $presets = StructureResolver::availablePresets($this->baseConfig);

        $this->assertContains('ddd-layered',  $presets);
        $this->assertContains('ddd-modular',  $presets);
        $this->assertContains('ddd-hexagonal', $presets);
        $this->assertContains('custom',        $presets);
    }

    /** @test */
    public function preset_exists_returns_true_for_known_preset(): void
    {
        $this->assertTrue(StructureResolver::presetExists($this->baseConfig, 'ddd-modular'));
    }

    /** @test */
    public function preset_exists_returns_false_for_unknown_preset(): void
    {
        $this->assertFalse(StructureResolver::presetExists($this->baseConfig, 'does-not-exist'));
    }

    /** @test */
    public function modular_preset_does_not_affect_non_overridden_keys(): void
    {
        $config = $this->baseConfig;
        $config['structure']       = 'ddd-modular';
        $config['generate_gitkeep'] = true;

        $resolved = StructureResolver::resolve($config);

        // generate_gitkeep is not in the modular preset — root value should survive
        $this->assertTrue($resolved['generate_gitkeep']);
    }
}
