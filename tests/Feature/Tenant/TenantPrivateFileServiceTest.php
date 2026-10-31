<?php

declare(strict_types=1);

namespace Tests\Feature\Tenant;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Modules\Core\Contracts\TenantPrivateFileServiceInterface;
use Tests\TestCase;

final class TenantPrivateFileServiceTest extends TestCase
{
    public function test_untrusted_filenames_do_not_disclose_directories_or_control_characters(): void
    {
        Storage::fake('tenant_private');

        $source = UploadedFile::fake()->createWithContent(
            'receipt.pdf',
            "%PDF-1.4\nPrivate receipt evidence\n%%EOF",
        );
        $service = app(TenantPrivateFileServiceInterface::class);

        foreach ([
            'C:\\Users\\Accountant\\Private\\receipt.pdf' => 'receipt.pdf',
            '/home/operator/private/receipt.pdf' => 'receipt.pdf',
            "receipt\r\nconfidential.pdf" => 'receiptconfidential.pdf',
            '   ' => 'file.bin',
            'ලදුපත.pdf' => 'ලදුපත.pdf',
        ] as $clientFilename => $expectedFilename) {
            $stored = $service->storeFile(
                1,
                'vehicle-rental/incidents/1',
                $source->getPathname(),
                $clientFilename,
                ['application/pdf'],
                1024,
            );

            self::assertSame($expectedFilename, $stored['original_filename']);
            Storage::disk('tenant_private')->assertExists('tenants/1/'.$stored['object_key']);
        }
    }
}
