<?php

declare(strict_types=1);

namespace Tests\Feature\Modules\Document;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

final class DocumentCrudEndpointTest extends TestCase
{
    use RefreshDatabase;

    protected bool $seed = true;

    public function test_document_setup_crud_and_preview_endpoints_work_with_backend_context(): void
    {
        [$tenantId, $organizationUnitId, $headers] = $this->authenticatedHeaders();

        $typeResponse = $this->withHeaders($headers)->postJson('/api/document/types', [
            'code' => 'DOC_TEST',
            'name' => 'Document Test Type',
            'description' => 'Feature-test document type.',
            'module_scope' => 'shared',
            'default_status' => 'draft',
            'is_active' => true,
            'requires_source' => false,
            'supports_items' => true,
            'supports_attachments' => true,
            'supports_comments' => true,
            'supports_versions' => true,
            'supports_workflow' => true,
        ]);

        $typeResponse
            ->assertCreated()
            ->assertJsonPath('data.code', 'DOC_TEST');

        $typeId = (int) $typeResponse->json('data.id');

        $this->withHeaders($headers)->putJson('/api/document/types/'.$typeId, [
            'code' => 'DOC_TEST',
            'name' => 'Document Test Type Updated',
            'description' => 'Updated feature-test document type.',
            'module_scope' => 'shared',
            'default_status' => 'draft',
            'is_active' => true,
            'requires_source' => false,
            'supports_items' => true,
            'supports_attachments' => true,
            'supports_comments' => true,
            'supports_versions' => true,
            'supports_workflow' => true,
        ])->assertOk()->assertJsonPath('data.name', 'Document Test Type Updated');

        $templateResponse = $this->withHeaders($headers)->postJson('/api/document/templates', [
            'document_type_id' => $typeId,
            'template_code' => 'TPL-DOC-TEST',
            'template_name' => 'Document Test Template',
            'layout_type' => 'html',
            'header_content' => '<header>{{document_number}}</header>',
            'body_content' => '<h1>{{document_title}}</h1><p>{{document_body}}</p>',
            'footer_content' => '<footer>Footer</footer>',
            'is_active' => true,
        ]);

        $templateResponse
            ->assertCreated()
            ->assertJsonPath('data.template_code', 'TPL-DOC-TEST');

        $templateId = (int) $templateResponse->json('data.id');

        $definitionResponse = $this->withHeaders($headers)->postJson('/api/document/definitions', [
            'document_type_id' => $typeId,
            'definition_code' => 'DEF-DOC-TEST',
            'version' => 1,
            'name' => 'Document Test Definition',
            'description' => 'Definition used by endpoint tests.',
            'source_module' => 'shared',
            'template_id' => $templateId,
            'default_status' => 'draft',
            'supports_versions' => true,
            'is_active' => true,
            'fields' => [
                [
                    'field_key' => 'title',
                    'label' => 'Title',
                    'data_type' => 'text',
                    'is_required' => true,
                    'display_order' => 1,
                ],
            ],
        ]);

        $definitionResponse
            ->assertCreated()
            ->assertJsonPath('data.definition.name', 'Document Test Definition');

        $definitionId = (int) $definitionResponse->json('data.definition.id');

        $this->withHeaders($headers)->putJson('/api/document/definitions/'.$definitionId, [
            'document_type_id' => $typeId,
            'definition_code' => 'DEF-DOC-TEST',
            'version' => 1,
            'name' => 'Document Test Definition Updated',
            'source_module' => 'shared',
            'template_id' => $templateId,
            'default_status' => 'draft',
            'supports_versions' => true,
            'is_active' => true,
            'fields' => [
                [
                    'field_key' => 'reference',
                    'label' => 'Reference',
                    'data_type' => 'text',
                    'is_required' => false,
                    'display_order' => 1,
                ],
            ],
        ])->assertOk()->assertJsonPath('data.name', 'Document Test Definition Updated');

        $this->withHeaders($headers)->postJson('/api/document/preview', [
            'definition_id' => $definitionId,
            'source_module' => 'shared',
            'source_reference' => 'DOC-TEST',
            'document_number' => 'DOC-TEST-0001',
            'metadata' => [
                'title' => 'Endpoint Preview',
                'body' => 'Rendered by backend.',
            ],
        ])
            ->assertOk()
            ->assertJsonPath('rendered.document_number', 'DOC-TEST-0001')
            ->assertJsonPath('metadata.business_logic_free', true);

        $documentResponse = $this->withHeaders($headers)->postJson('/api/document/documents', [
            'document_type_id' => $typeId,
            'document_definition_id' => $definitionId,
            'organization_unit_id' => $organizationUnitId,
            'document_date' => now()->toDateString(),
            'title' => 'Generic Source Document',
            'source_module' => 'shared',
            'source_type' => 'reference',
            'source_id' => 1001,
            'source_reference' => 'SRC-1001',
            'notes' => 'Created from generic source data.',
            'items' => [
                [
                    'item_type' => 'generic',
                    'description' => 'Document-ready line supplied by source module.',
                    'line_total' => '25.0000',
                ],
            ],
        ]);

        $documentResponse
            ->assertCreated()
            ->assertJsonPath('data.source_module', 'shared')
            ->assertJsonPath('data.source_type', 'reference')
            ->assertJsonPath('data.source_id', 1001)
            ->assertJsonPath('data.source_reference', 'SRC-1001')
            ->assertJsonPath('data.title', 'Generic Source Document');

        $documentId = (int) $documentResponse->json('data.id');

        $this->withHeaders($headers)->postJson('/api/document/documents/'.$documentId.'/preview')
            ->assertOk()
            ->assertJsonPath('rendered.document_number', $documentResponse->json('data.document_number'))
            ->assertJsonPath('metadata.business_logic_free', true);

        $this->withHeaders($headers)->getJson('/api/document/workflows')
            ->assertOk()
            ->assertJsonStructure(['data']);

        self::assertSame($tenantId, (int) DB::table('document_types')->where('id', $typeId)->value('tenant_id'));
        self::assertSame($organizationUnitId, $organizationUnitId);
    }

    public function test_document_validation_errors_are_returned(): void
    {
        [, , $headers] = $this->authenticatedHeaders();

        $this->withHeaders($headers)
            ->postJson('/api/document/types', ['name' => ''])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['name', 'code']);
    }

    /**
     * @return array{0:int,1:int,2:array<string,string>}
     */
    private function authenticatedHeaders(): array
    {
        $tenantId = (int) DB::table('tenants')->where('code', 'AUTOERP')->value('id');
        $organizationUnitId = (int) DB::table('organization_units')
            ->where('tenant_id', $tenantId)
            ->where('code', 'MAIN')
            ->value('id');

        $loginResponse = $this->postJson('/api/auth/login', [
            'login_identifier' => 'admin@example.com',
            'password' => 'password',
            'provider_key' => 'internal',
            'tenant_id' => $tenantId,
        ]);

        $loginResponse->assertOk();

        return [
            $tenantId,
            $organizationUnitId,
            [
                'Authorization' => 'Bearer '.(string) $loginResponse->json('data.tokens.access_token'),
                'X-Organization-Unit-ID' => (string) $organizationUnitId,
                'X-Tenant-ID' => (string) $tenantId,
            ],
        ];
    }
}
