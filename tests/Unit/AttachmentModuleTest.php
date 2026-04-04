<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use Modules\Attachment\Domain\ValueObjects\AttachableType;
use Modules\Attachment\Domain\Entities\Attachment;

class AttachmentModuleTest extends TestCase
{
    // --- AttachableType constants ---

    public function test_attachable_type_purchase_order_constant(): void
    {
        $this->assertSame('purchase_order', AttachableType::PURCHASE_ORDER);
    }

    public function test_attachable_type_goods_receipt_constant(): void
    {
        $this->assertSame('goods_receipt', AttachableType::GOODS_RECEIPT);
    }

    public function test_attachable_type_sales_order_constant(): void
    {
        $this->assertSame('sales_order', AttachableType::SALES_ORDER);
    }

    public function test_attachable_type_stock_return_constant(): void
    {
        $this->assertSame('stock_return', AttachableType::STOCK_RETURN);
    }

    public function test_attachable_type_product_constant(): void
    {
        $this->assertSame('product', AttachableType::PRODUCT);
    }

    public function test_attachable_type_supplier_constant(): void
    {
        $this->assertSame('supplier', AttachableType::SUPPLIER);
    }

    public function test_attachable_type_customer_constant(): void
    {
        $this->assertSame('customer', AttachableType::CUSTOMER);
    }

    public function test_attachable_type_dispatch_constant(): void
    {
        $this->assertSame('dispatch', AttachableType::DISPATCH);
    }

    // --- Attachment entity ---

    public function test_attachment_construction_all_fields(): void
    {
        $attachment = new Attachment(
            id: 1,
            tenantId: 5,
            attachableType: AttachableType::PURCHASE_ORDER,
            attachableId: 100,
            disk: 's3',
            path: 'uploads/po/file.pdf',
            originalName: 'invoice.pdf',
            mimeType: 'application/pdf',
            size: 204800,
            label: 'Invoice',
            uploadedBy: 7,
        );

        $this->assertSame(1,                              $attachment->id);
        $this->assertSame(5,                              $attachment->tenantId);
        $this->assertSame(AttachableType::PURCHASE_ORDER, $attachment->attachableType);
        $this->assertSame(100,                            $attachment->attachableId);
        $this->assertSame('s3',                           $attachment->disk);
        $this->assertSame('uploads/po/file.pdf',          $attachment->path);
        $this->assertSame('invoice.pdf',                  $attachment->originalName);
        $this->assertSame('application/pdf',              $attachment->mimeType);
        $this->assertSame(204800,                         $attachment->size);
        $this->assertSame('Invoice',                      $attachment->label);
        $this->assertSame(7,                              $attachment->uploadedBy);
    }

    public function test_attachment_optional_fields_default_to_null(): void
    {
        $attachment = new Attachment(
            id: null,
            tenantId: 1,
            attachableType: AttachableType::SALES_ORDER,
            attachableId: 50,
            disk: 'local',
            path: 'uploads/so/doc.png',
            originalName: 'receipt.png',
            mimeType: 'image/png',
            size: 1024,
        );

        $this->assertNull($attachment->id);
        $this->assertNull($attachment->label);
        $this->assertNull($attachment->uploadedBy);
    }
}
