<?php

declare(strict_types=1);

/**
 * AutoERP Enterprise System
 *
 * @author Kasun Vimarshana <kasunvmail@gmail.com>
 *
 * Phone: +94713525253
 */

namespace Modules\Invoice\Tests;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Modules\Invoice\DTOs\InvoiceLayoutData;
use Modules\Invoice\Enums\InvoiceLayoutTemplate;
use Modules\Invoice\Enums\InvoiceLayoutType;
use Modules\Invoice\Enums\InvoiceOrientation;
use Modules\Invoice\Enums\InvoicePaperSize;
use Modules\Invoice\Http\Requests\StoreInvoiceLayoutRequest;
use Modules\Invoice\Http\Requests\UpdateInvoiceLayoutRequest;
use Modules\Invoice\Models\InvoiceLayout;
use Modules\Invoice\Services\InvoiceLayoutService;
use Modules\Invoice\Validators\InvoiceCssSanitizer;
use Tests\Support\CurrencyFixture;
use Tests\TestCase;

final class InvoiceLayoutCssSecurityTest extends TestCase
{
    use RefreshDatabase;

    public function test_store_and_update_layout_requests_reject_xss_payloads_in_custom_css(): void
    {
        $payloads = [
            'body { color: red; }</style><script>alert(1)</script>',
            '</ style><img src=x onerror=alert(1)>',
            '<script>alert(1)</script>',
            'body { background: url(javascript:alert(1)); }',
            'body { width: expression(alert(1)); }',
            'div { color: red; } <svg onload=alert(1)>',
        ];

        $storeRules = (new StoreInvoiceLayoutRequest)->rules();
        $updateRules = (new UpdateInvoiceLayoutRequest)->rules();

        foreach ($payloads as $payload) {
            $storeValidator = Validator::make([
                'code' => 'CUSTOM_XSS_TEST',
                'name' => 'Custom Layout',
                'paper_size' => InvoicePaperSize::A4->value,
                'orientation' => InvoiceOrientation::Portrait->value,
                'template_type' => InvoiceLayoutType::Preset->value,
                'view_template' => InvoiceLayoutTemplate::Standard->value,
                'custom_css' => $payload,
            ], $storeRules);

            $this->assertTrue($storeValidator->fails(), "StoreInvoiceLayoutRequest should reject payload: {$payload}");
            $this->assertArrayHasKey('custom_css', $storeValidator->errors()->toArray());

            $updateValidator = Validator::make([
                'expected_version' => 1,
                'name' => 'Custom Layout',
                'custom_css' => $payload,
            ], $updateRules);

            $this->assertTrue($updateValidator->fails(), "UpdateInvoiceLayoutRequest should reject payload: {$payload}");
            $this->assertArrayHasKey('custom_css', $updateValidator->errors()->toArray());
        }
    }

    public function test_valid_custom_css_with_child_combinator_is_accepted(): void
    {
        $rules = (new StoreInvoiceLayoutRequest)->rules();

        $validator = Validator::make([
            'code' => 'CUSTOM_VALID_CSS',
            'name' => 'Valid Custom Layout',
            'paper_size' => InvoicePaperSize::A4->value,
            'orientation' => InvoiceOrientation::Portrait->value,
            'template_type' => InvoiceLayoutType::Preset->value,
            'view_template' => InvoiceLayoutTemplate::Standard->value,
            'custom_css' => '.sheet > .header { color: #111827; font-size: 12px; }',
        ], $rules);

        $this->assertFalse($validator->fails(), 'Valid CSS with child combinator > should be accepted.');
    }

    public function test_invoice_layout_service_rejects_style_breakout_in_custom_css(): void
    {
        $tenantId = 1;
        $service = app(InvoiceLayoutService::class);

        $this->expectException(ValidationException::class);

        $service->create($tenantId, new InvoiceLayoutData(
            code: 'SVC_XSS_TEST',
            name: 'XSS Test',
            customCss: 'body { color: #000; } </style><script>alert(1)</script>',
        ), 1);
    }

    public function test_print_blade_defensively_strips_dangerous_custom_css(): void
    {
        $currencyId = CurrencyFixture::create(['name' => 'Invoice Security Test Currency']);
        $tenantId = (int) DB::table('tenants')->insertGetId([
            'uuid' => (string) Str::uuid(),
            'code' => 'INVSEC',
            'name' => 'Invoice Security Tenant',
            'slug' => 'invsec',
            'status' => 'active',
            'status_changed_at' => now(),
            'base_currency_id' => $currencyId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $maliciousCss = 'body { color: #111; } </style><script>alert("xss")</script><img src=x onerror=alert(1)>';

        $layout = new InvoiceLayout([
            'tenant_id' => $tenantId,
            'code' => 'LEGACY_XSS',
            'name' => 'Legacy XSS',
            'paper_size' => InvoicePaperSize::A4->value,
            'orientation' => InvoiceOrientation::Portrait->value,
            'template_type' => InvoiceLayoutType::Preset->value,
            'view_template' => InvoiceLayoutTemplate::Modern->value,
            'custom_css' => $maliciousCss,
            'is_active' => true,
        ]);
        $layout->save();

        $html = app(InvoiceLayoutService::class)->previewHtml($tenantId, $layout->id);

        $this->assertStringNotContainsString('alert("xss")', $html);
        $this->assertStringNotContainsString('onerror=alert', strtolower($html));
        $this->assertStringContainsString('body { color: #111; }', $html);
        $this->assertNull(InvoiceCssSanitizer::sanitize('</style><script>alert(1)</script>'));
    }
}
