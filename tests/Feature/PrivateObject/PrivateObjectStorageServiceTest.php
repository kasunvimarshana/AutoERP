<?php

declare(strict_types=1);

namespace Tests\Feature\PrivateObject;

use Illuminate\Support\Facades\Storage;
use InvalidArgumentException;
use Modules\PrivateObject\Services\PrivateObjectStorageService;
use Tests\TestCase;

final class PrivateObjectStorageServiceTest extends TestCase
{
    public function test_it_streams_a_source_file_and_blocks_path_traversal(): void
    {
        Storage::fake('core-test');
        $source = tempnam(sys_get_temp_dir(), 'core-file-');
        self::assertIsString($source);
        file_put_contents($source, 'content');

        try {
            $service = new PrivateObjectStorageService('core-test');
            $path = $service->store($source, 'tenant/1', 'document.txt');

            self::assertSame('tenant/1/document.txt', $path);
            Storage::disk('core-test')->assertExists($path);

            $stream = $service->readStream($path);
            self::assertIsResource($stream);
            self::assertSame('content', stream_get_contents($stream));
            fclose($stream);

            $this->expectException(InvalidArgumentException::class);
            $service->readStream('../outside.txt');
        } finally {
            @unlink($source);
        }
    }
}
