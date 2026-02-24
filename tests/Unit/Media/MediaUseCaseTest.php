<?php

namespace Tests\Unit\Media;

use DomainException;
use Illuminate\Contracts\Filesystem\Factory as FilesystemFactory;
use Illuminate\Contracts\Filesystem\Filesystem;
use Mockery;
use Modules\Media\Application\UseCases\DeleteMediaUseCase;
use Modules\Media\Application\UseCases\UploadFileUseCase;
use Modules\Media\Domain\Contracts\MediaRepositoryInterface;
use Modules\Media\Domain\Enums\MediaDisk;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for Media module.
 *
 * UploadFileUseCase orchestrates Storage, DB, and Eloquent which are
 * integration concerns tested at the feature layer. Here we cover the
 * pure-domain components (MediaDisk enum) and the structural contract of
 * the use case class.
 */
class MediaUseCaseTest extends TestCase
{
    // -------------------------------------------------------------------------
    // MediaDisk enum
    // -------------------------------------------------------------------------

    public function test_media_disk_enum_has_expected_cases(): void
    {
        $cases = array_map(fn ($c) => $c->value, MediaDisk::cases());

        $this->assertContains('local', $cases);
        $this->assertContains('s3', $cases);
        $this->assertContains('gcs', $cases);
        $this->assertContains('azure', $cases);
    }

    public function test_media_disk_from_returns_correct_enum(): void
    {
        $this->assertSame(MediaDisk::Local, MediaDisk::from('local'));
        $this->assertSame(MediaDisk::S3,    MediaDisk::from('s3'));
        $this->assertSame(MediaDisk::Gcs,   MediaDisk::from('gcs'));
        $this->assertSame(MediaDisk::Azure, MediaDisk::from('azure'));
    }

    public function test_media_disk_try_from_returns_null_for_unknown_disk(): void
    {
        $this->assertNull(MediaDisk::tryFrom('ftp'));
        $this->assertNull(MediaDisk::tryFrom(''));
    }

    public function test_media_disk_all_values_are_lowercase_strings(): void
    {
        foreach (MediaDisk::cases() as $case) {
            $this->assertSame(strtolower($case->value), $case->value,
                "MediaDisk::{$case->name} value should be lowercase");
        }
    }

    // -------------------------------------------------------------------------
    // UploadFileUseCase contract
    // -------------------------------------------------------------------------

    public function test_upload_file_use_case_class_exists_and_has_execute_method(): void
    {
        $this->assertTrue(class_exists(UploadFileUseCase::class));
        $this->assertTrue(method_exists(UploadFileUseCase::class, 'execute'));
    }

    public function test_upload_file_use_case_execute_accepts_array_parameter(): void
    {
        $reflection = new \ReflectionMethod(UploadFileUseCase::class, 'execute');
        $params     = $reflection->getParameters();

        $this->assertCount(1, $params);
        $this->assertSame('data', $params[0]->getName());
        $this->assertTrue($params[0]->hasType());
        $this->assertSame('array', (string) $params[0]->getType());
    }

    // -------------------------------------------------------------------------
    // DeleteMediaUseCase
    // -------------------------------------------------------------------------

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    private function makeMediaStub(string $disk = 'local', string $path = 'uploads/test.pdf'): object
    {
        return (object) [
            'id'   => 'media-uuid-1',
            'disk' => $disk,
            'path' => $path,
        ];
    }

    private function makeFilesystemFactory(string $disk, Filesystem $diskMock): FilesystemFactory
    {
        $factory = Mockery::mock(FilesystemFactory::class);
        $factory->shouldReceive('disk')->with($disk)->andReturn($diskMock);

        return $factory;
    }

    public function test_delete_throws_domain_exception_when_media_not_found(): void
    {
        $repo = Mockery::mock(MediaRepositoryInterface::class);
        $repo->shouldReceive('findById')->with('missing-id')->andReturn(null);

        $fs = Mockery::mock(FilesystemFactory::class);

        $useCase = new DeleteMediaUseCase($repo, $fs);

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('Media file not found.');

        $useCase->execute('missing-id');
    }

    public function test_delete_removes_storage_file_and_calls_repository_delete(): void
    {
        $media = $this->makeMediaStub();

        $repo = Mockery::mock(MediaRepositoryInterface::class);
        $repo->shouldReceive('findById')->with($media->id)->andReturn($media);
        $repo->shouldReceive('delete')->with($media->id)->once();

        $disk = Mockery::mock(Filesystem::class);
        $disk->shouldReceive('delete')->with($media->path)->once()->andReturn(true);

        $useCase = new DeleteMediaUseCase($repo, $this->makeFilesystemFactory($media->disk, $disk));
        $useCase->execute($media->id);

        $this->assertTrue(true);
    }

    public function test_delete_handles_already_absent_file_gracefully(): void
    {
        $media = $this->makeMediaStub();

        $repo = Mockery::mock(MediaRepositoryInterface::class);
        $repo->shouldReceive('findById')->with($media->id)->andReturn($media);
        $repo->shouldReceive('delete')->with($media->id)->once();

        $disk = Mockery::mock(Filesystem::class);
        // delete returns false when file does not exist — use case must not throw
        $disk->shouldReceive('delete')->with($media->path)->once()->andReturn(false);

        $useCase = new DeleteMediaUseCase($repo, $this->makeFilesystemFactory($media->disk, $disk));
        $useCase->execute($media->id);

        // No exception thrown; Mockery verifies both delete() calls were made exactly once.
        $this->assertTrue(true);
    }
}
