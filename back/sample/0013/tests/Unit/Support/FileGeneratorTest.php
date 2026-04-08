<?php

declare(strict_types=1);

namespace Archify\DddArchitect\Tests\Unit\Support;

use Archify\DddArchitect\Support\FileGenerator;
use Archify\DddArchitect\Support\GenerationResult;
use Archify\DddArchitect\Tests\TestCase;

/**
 * @covers \Archify\DddArchitect\Support\FileGenerator
 * @covers \Archify\DddArchitect\Support\GenerationResult
 */
final class FileGeneratorTest extends TestCase
{
    private FileGenerator $generator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->generator = $this->app->make(FileGenerator::class);
    }

    /** @test */
    public function it_creates_a_new_file(): void
    {
        $path   = $this->tempDir . '/NewFile.php';
        $result = $this->generator->write($path, '<?php // test');

        $this->assertTrue($result->wasCreated());
        $this->assertFileExists($path);
        $this->assertStringContainsString('// test', file_get_contents($path));
    }

    /** @test */
    public function it_creates_parent_directories(): void
    {
        $path   = $this->tempDir . '/deep/nested/dir/File.php';
        $result = $this->generator->write($path, '<?php');

        $this->assertTrue($result->wasCreated());
        $this->assertFileExists($path);
    }

    /** @test */
    public function it_skips_existing_file_without_force(): void
    {
        $path = $this->tempDir . '/Existing.php';
        file_put_contents($path, '<?php // original');

        $result = $this->generator->write($path, '<?php // new', false);

        $this->assertTrue($result->wasSkipped());
        $this->assertStringContainsString('original', file_get_contents($path));
    }

    /** @test */
    public function it_overwrites_existing_file_with_force(): void
    {
        $path = $this->tempDir . '/Existing.php';
        file_put_contents($path, '<?php // original');

        $result = $this->generator->write($path, '<?php // replaced', true);

        $this->assertTrue($result->wasCreated());
        $this->assertStringContainsString('replaced', file_get_contents($path));
    }

    /** @test */
    public function it_returns_dry_run_result_when_configured(): void
    {
        config(['ddd-architect.generator.dry_run' => true]);

        $path   = $this->tempDir . '/DryRun.php';
        $result = $this->generator->write($path, '<?php');

        $this->assertTrue($result->isDryRun());
        $this->assertFileDoesNotExist($path);

        config(['ddd-architect.generator.dry_run' => false]);
    }

    /** @test */
    public function generation_result_statuses_are_mutually_exclusive(): void
    {
        $created = GenerationResult::created('/foo');
        $skipped = GenerationResult::skipped('/foo');
        $dry     = GenerationResult::dryRun('/foo');

        $this->assertTrue($created->wasCreated());
        $this->assertFalse($created->wasSkipped());
        $this->assertFalse($created->isDryRun());

        $this->assertTrue($skipped->wasSkipped());
        $this->assertFalse($skipped->wasCreated());

        $this->assertTrue($dry->isDryRun());
        $this->assertFalse($dry->wasCreated());
    }

    /** @test */
    public function it_ensures_directory_exists(): void
    {
        $dir = $this->tempDir . '/new/nested/dir';
        $this->generator->ensureDirectory($dir);

        $this->assertDirectoryExists($dir);
    }
}
