<?php

declare(strict_types=1);

namespace Tests\Unit\PrivateObject;

use Illuminate\Support\Facades\Storage;
use InvalidArgumentException;
use Modules\PrivateObject\Services\PrivateObjectStorageService;
use Tests\TestCase;

final class PrivateObjectStorageServiceBehaviorTest extends TestCase
{
    private const DEFAULT_DISK = 'private-object-test';
    private const OTHER_DISK = 'private-object-other-test';
    private const DIRECTORY = 'tenants/10/documents';
    private const FILENAME = 'evidence.txt';
    private const CONTENT = 'private object evidence';

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake(self::DEFAULT_DISK);
        Storage::fake(self::OTHER_DISK);
    }

    public function test_complete_file_lifecycle_uses_normalized_relative_paths(): void
    {
        $source = $this->sourceFile(self::CONTENT);

        try {
            $storage = new PrivateObjectStorageService(self::DEFAULT_DISK);
            $path = $storage->store($source, self::DIRECTORY, self::FILENAME);

            self::assertSame(self::DIRECTORY.'/'.self::FILENAME, $path);
            self::assertTrue($storage->exists($path));
            self::assertSame(strlen(self::CONTENT), $storage->size($path));
            self::assertContains($path, $storage->allFiles(self::DIRECTORY));

            $stream = $storage->readStream($path);
            try {
                self::assertSame(self::CONTENT, stream_get_contents($stream));
            } finally {
                fclose($stream);
            }

            self::assertTrue($storage->delete($path));
            self::assertFalse($storage->exists($path));
        } finally {
            @unlink($source);
        }
    }

    public function test_explicit_disk_override_does_not_write_to_the_default_disk(): void
    {
        $source = $this->sourceFile(self::CONTENT);

        try {
            $storage = new PrivateObjectStorageService(self::DEFAULT_DISK);
            $path = $storage->store($source, self::DIRECTORY, self::FILENAME, self::OTHER_DISK);

            self::assertFalse(Storage::disk(self::DEFAULT_DISK)->exists($path));
            self::assertTrue(Storage::disk(self::OTHER_DISK)->exists($path));
            self::assertTrue($storage->exists($path, self::OTHER_DISK));
        } finally {
            @unlink($source);
        }
    }

    public function test_invalid_source_filename_paths_and_disk_are_rejected(): void
    {
        $storage = new PrivateObjectStorageService(self::DEFAULT_DISK);

        $this->assertInvalid(
            fn () => $storage->store('/missing/private-object-source', self::DIRECTORY, self::FILENAME),
            'Source file is not readable.',
        );

        $source = $this->sourceFile(self::CONTENT);
        try {
            $this->assertInvalid(
                fn () => $storage->store($source, self::DIRECTORY, '../evidence.txt'),
                'Filename must be a safe basename.',
            );
            $this->assertInvalid(
                fn () => $storage->exists('tenants/10/../secret.txt'),
                'Storage path contains an invalid segment.',
            );
            $this->assertInvalid(
                fn () => $storage->allFiles(''),
                'Storage path must be a non-empty relative path.',
            );
            $this->assertInvalid(
                fn () => new PrivateObjectStorageService('   '),
                'Storage disk cannot be empty.',
            );
        } finally {
            @unlink($source);
        }
    }

    private function sourceFile(string $content): string
    {
        $path = tempnam(sys_get_temp_dir(), 'autoerp-private-object-');
        if ($path === false) {
            self::fail('Unable to create a temporary private-object source file.');
        }
        file_put_contents($path, $content);

        return $path;
    }

    private function assertInvalid(callable $operation, string $message): void
    {
        try {
            $operation();
            self::fail('Expected private-object validation to fail.');
        } catch (InvalidArgumentException $exception) {
            self::assertSame($message, $exception->getMessage());
        }
    }
}
