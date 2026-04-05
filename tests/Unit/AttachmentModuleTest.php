<?php
declare(strict_types=1);
namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use Modules\Attachment\Domain\Entities\Attachment;

class AttachmentModuleTest extends TestCase
{
    private function makeAttachment(array $overrides = []): Attachment
    {
        return new Attachment(
            1, 1, 'product', 10, 'file_abc.pdf', 'product-spec.pdf',
            $overrides['mime'] ?? 'application/pdf', 1024, 'attachments/1/product/10/file_abc.pdf',
            'local', 'spec', 1, null, null
        );
    }

    public function test_attachment_creation(): void
    {
        $att = $this->makeAttachment();
        $this->assertEquals('product', $att->getAttachableType());
        $this->assertEquals(10, $att->getAttachableId());
        $this->assertEquals('product-spec.pdf', $att->getOriginalName());
        $this->assertEquals(1024, $att->getSize());
    }

    public function test_attachment_is_pdf(): void
    {
        $att = $this->makeAttachment(['mime' => 'application/pdf']);
        $this->assertTrue($att->isPdf());
        $this->assertFalse($att->isImage());
    }

    public function test_attachment_is_image(): void
    {
        $att = $this->makeAttachment(['mime' => 'image/jpeg']);
        $this->assertTrue($att->isImage());
        $this->assertFalse($att->isPdf());
    }

    public function test_attachment_category(): void
    {
        $att = $this->makeAttachment();
        $this->assertEquals('spec', $att->getCategory());
    }

    // ──────────────────────────────────────────────────────────────────────
    // Attachment – additional coverage
    // ──────────────────────────────────────────────────────────────────────

    public function test_attachment_is_png_image(): void
    {
        $att = $this->makeAttachment(['mime' => 'image/png']);
        $this->assertTrue($att->isImage());
        $this->assertFalse($att->isPdf());
    }

    public function test_attachment_is_video(): void
    {
        $att = $this->makeAttachment(['mime' => 'video/mp4']);
        $this->assertFalse($att->isImage());
        $this->assertFalse($att->isPdf());
    }

    public function test_attachment_filename_and_path(): void
    {
        $att = $this->makeAttachment();
        $this->assertEquals('file_abc.pdf', $att->getFilename());
        $this->assertEquals('attachments/1/product/10/file_abc.pdf', $att->getPath());
        $this->assertEquals('local', $att->getDisk());
    }

    public function test_attachment_tenant_and_uploader(): void
    {
        $att = $this->makeAttachment();
        $this->assertEquals(1, $att->getTenantId());
        $this->assertEquals(1, $att->getUploadedBy());
    }

    public function test_attachment_with_no_disk(): void
    {
        $att = new Attachment(
            1, 1, 'invoice', 5, 'inv.pdf', 'Invoice-2024.pdf',
            'application/pdf', 2048, 'files/inv.pdf', null,
            null, null, null, null
        );
        $this->assertNull($att->getDisk());
        $this->assertNull($att->getCategory());
        $this->assertNull($att->getUploadedBy());
    }

    public function test_attachment_size(): void
    {
        $att = $this->makeAttachment();
        $this->assertEquals(1024, $att->getSize());
    }

    public function test_attachment_polymorphic_types(): void
    {
        foreach (['product', 'purchase_order', 'sales_order', 'return_request'] as $type) {
            $att = new Attachment(1, 1, $type, 1, 'f.pdf', 'f.pdf', 'application/pdf', 100, 'f.pdf', null, null, null, null, null);
            $this->assertEquals($type, $att->getAttachableType());
        }
    }
}
