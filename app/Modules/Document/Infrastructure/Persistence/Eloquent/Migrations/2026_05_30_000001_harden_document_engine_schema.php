<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('document_types', function (Blueprint $table): void {
            if (! Schema::hasColumn('document_types', 'tenant_id')) {
                $table->unsignedBigInteger('tenant_id')->nullable()->after('id');
            }
            if (! Schema::hasColumn('document_types', 'organization_unit_id')) {
                $table->unsignedBigInteger('organization_unit_id')->nullable()->after('tenant_id');
            }
            if (! Schema::hasColumn('document_types', 'description')) {
                $table->text('description')->nullable()->after('name');
            }
            if (! Schema::hasColumn('document_types', 'module_scope')) {
                $table->string('module_scope', 120)->default('shared')->after('description');
            }
            if (! Schema::hasColumn('document_types', 'supports_items')) {
                $table->boolean('supports_items')->default(true)->after('requires_source');
            }
            if (! Schema::hasColumn('document_types', 'supports_attachments')) {
                $table->boolean('supports_attachments')->default(true)->after('supports_items');
            }
            if (! Schema::hasColumn('document_types', 'supports_comments')) {
                $table->boolean('supports_comments')->default(true)->after('supports_attachments');
            }
            if (! Schema::hasColumn('document_types', 'supports_versions')) {
                $table->boolean('supports_versions')->default(true)->after('supports_comments');
            }
            if (! Schema::hasColumn('document_types', 'supports_workflow')) {
                $table->boolean('supports_workflow')->default(true)->after('supports_versions');
            }
            if (! Schema::hasColumn('document_types', 'created_by')) {
                $table->unsignedBigInteger('created_by')->nullable()->after('supports_workflow');
            }
            if (! Schema::hasColumn('document_types', 'updated_by')) {
                $table->unsignedBigInteger('updated_by')->nullable()->after('created_by');
            }
            $table->index(['tenant_id', 'module_scope', 'is_active'], 'document_types_tenant_scope_active_idx');
        });

        Schema::table('document_definitions', function (Blueprint $table): void {
            if (! Schema::hasColumn('document_definitions', 'organization_unit_id')) {
                $table->unsignedBigInteger('organization_unit_id')->nullable()->after('tenant_id');
            }
            if (! Schema::hasColumn('document_definitions', 'definition_code')) {
                $table->string('definition_code', 120)->nullable()->after('document_type_id');
            }
            if (! Schema::hasColumn('document_definitions', 'description')) {
                $table->text('description')->nullable()->after('name');
            }
            if (! Schema::hasColumn('document_definitions', 'source_module')) {
                $table->string('source_module', 120)->default('shared')->after('description');
            }
            if (! Schema::hasColumn('document_definitions', 'template_id')) {
                $table->unsignedBigInteger('template_id')->nullable()->after('source_module');
            }
            if (! Schema::hasColumn('document_definitions', 'sequence_id')) {
                $table->unsignedBigInteger('sequence_id')->nullable()->after('template_id');
            }
            if (! Schema::hasColumn('document_definitions', 'workflow_id')) {
                $table->unsignedBigInteger('workflow_id')->nullable()->after('sequence_id');
            }
            if (! Schema::hasColumn('document_definitions', 'default_status')) {
                $table->string('default_status', 120)->default('draft')->after('workflow_id');
            }
            if (! Schema::hasColumn('document_definitions', 'supports_versions')) {
                $table->boolean('supports_versions')->default(true)->after('default_status');
            }
            if (! Schema::hasColumn('document_definitions', 'created_by')) {
                $table->unsignedBigInteger('created_by')->nullable()->after('is_active');
            }
            if (! Schema::hasColumn('document_definitions', 'updated_by')) {
                $table->unsignedBigInteger('updated_by')->nullable()->after('created_by');
            }
            $table->index(['tenant_id', 'source_module', 'is_active'], 'document_definitions_source_active_idx');
            $table->index(['tenant_id', 'definition_code'], 'document_definitions_code_idx');
        });

        Schema::table('document_definition_fields', function (Blueprint $table): void {
            if (! Schema::hasColumn('document_definition_fields', 'section_key')) {
                $table->string('section_key', 120)->nullable()->after('document_definition_id');
            }
            if (! Schema::hasColumn('document_definition_fields', 'is_readonly')) {
                $table->boolean('is_readonly')->default(false)->after('is_required');
            }
            if (! Schema::hasColumn('document_definition_fields', 'is_active')) {
                $table->boolean('is_active')->default(true)->after('validation_rule');
            }
        });

        Schema::create('document_templates', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->nullable()->constrained('tenants')->cascadeOnDelete();
            $table->unsignedBigInteger('organization_unit_id')->nullable();
            $table->foreignId('document_type_id')->nullable()->constrained('document_types')->nullOnDelete();
            $table->string('template_code', 120);
            $table->string('template_name');
            $table->string('layout_type', 80)->default('html');
            $table->text('header_content')->nullable();
            $table->text('body_content')->nullable();
            $table->text('footer_content')->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();

            $table->unique(['tenant_id', 'template_code'], 'document_templates_tenant_code_unique');
            $table->index(['tenant_id', 'document_type_id', 'is_active'], 'document_templates_tenant_type_active_idx');
        });

        Schema::create('document_template_sections', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->nullable()->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('document_template_id')->constrained('document_templates')->cascadeOnDelete();
            $table->string('section_key', 120);
            $table->string('section_label', 180);
            $table->string('section_type', 80)->default('content');
            $table->unsignedInteger('display_order')->default(1);
            $table->text('content')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['tenant_id', 'document_template_id', 'section_key'], 'document_template_sections_unique');
            $table->index(['tenant_id', 'document_template_id', 'display_order'], 'document_template_sections_order_idx');
        });

        Schema::create('document_template_fields', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->nullable()->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('document_template_id')->constrained('document_templates')->cascadeOnDelete();
            $table->foreignId('document_template_section_id')->nullable()->constrained('document_template_sections')->nullOnDelete();
            $table->string('field_key', 120);
            $table->string('label', 180)->nullable();
            $table->string('placeholder', 180);
            $table->string('field_role', 80)->default('metadata');
            $table->unsignedInteger('display_order')->default(1);
            $table->boolean('is_required')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['tenant_id', 'document_template_id', 'field_key'], 'document_template_fields_unique');
            $table->index(['tenant_id', 'document_template_id', 'display_order'], 'document_template_fields_order_idx');
        });

        Schema::table('documents', function (Blueprint $table): void {
            if (! Schema::hasColumn('documents', 'document_definition_id')) {
                $table->unsignedBigInteger('document_definition_id')->nullable()->after('document_type_id');
            }
            if (! Schema::hasColumn('documents', 'source_module')) {
                $table->string('source_module', 120)->nullable()->after('party_id');
            }
            if (! Schema::hasColumn('documents', 'source_type')) {
                $table->string('source_type', 120)->nullable()->after('source_module');
            }
            if (! Schema::hasColumn('documents', 'source_id')) {
                $table->unsignedBigInteger('source_id')->nullable()->after('source_type');
            }
            if (! Schema::hasColumn('documents', 'source_reference')) {
                $table->string('source_reference', 180)->nullable()->after('source_id');
            }
            if (! Schema::hasColumn('documents', 'title')) {
                $table->string('title')->nullable()->after('source_reference');
            }
            if (! Schema::hasColumn('documents', 'version')) {
                $table->unsignedInteger('version')->default(1)->after('status');
            }
            if (! Schema::hasColumn('documents', 'effective_date')) {
                $table->date('effective_date')->nullable()->after('due_date');
            }
            if (! Schema::hasColumn('documents', 'expiry_date')) {
                $table->date('expiry_date')->nullable()->after('effective_date');
            }
            if (! Schema::hasColumn('documents', 'currency_id')) {
                $table->unsignedBigInteger('currency_id')->nullable()->after('expiry_date');
            }
            if (! Schema::hasColumn('documents', 'posted_at')) {
                $table->timestamp('posted_at')->nullable()->after('updated_by');
            }
            if (! Schema::hasColumn('documents', 'posted_by')) {
                $table->unsignedBigInteger('posted_by')->nullable()->after('posted_at');
            }
            if (! Schema::hasColumn('documents', 'finalized_at')) {
                $table->timestamp('finalized_at')->nullable()->after('posted_by');
            }
            if (! Schema::hasColumn('documents', 'finalized_by')) {
                $table->unsignedBigInteger('finalized_by')->nullable()->after('finalized_at');
            }
            $table->index(['tenant_id', 'source_module', 'source_type', 'source_id'], 'documents_source_reference_idx');
            $table->index(['tenant_id', 'document_definition_id', 'status'], 'documents_definition_status_idx');
        });

        Schema::table('document_items', function (Blueprint $table): void {
            if (! Schema::hasColumn('document_items', 'line_no')) {
                $table->unsignedInteger('line_no')->default(1)->after('document_id');
            }
            if (! Schema::hasColumn('document_items', 'line_type')) {
                $table->string('line_type', 120)->default('line')->after('line_no');
            }
            if (! Schema::hasColumn('document_items', 'item_label')) {
                $table->string('item_label')->nullable()->after('item_type');
            }
            if (! Schema::hasColumn('document_items', 'quantity')) {
                $table->decimal('quantity', 20, 6)->nullable()->after('description');
            }
            if (! Schema::hasColumn('document_items', 'uom_label')) {
                $table->string('uom_label', 80)->nullable()->after('quantity');
            }
            if (! Schema::hasColumn('document_items', 'unit_price')) {
                $table->decimal('unit_price', 20, 4)->nullable()->after('uom_label');
            }
            if (! Schema::hasColumn('document_items', 'discount_amount')) {
                $table->decimal('discount_amount', 20, 4)->default(0)->after('unit_price');
            }
            if (! Schema::hasColumn('document_items', 'tax_amount')) {
                $table->decimal('tax_amount', 20, 4)->default(0)->after('discount_amount');
            }
            if (! Schema::hasColumn('document_items', 'source_line_type')) {
                $table->string('source_line_type', 120)->nullable()->after('line_total');
            }
            if (! Schema::hasColumn('document_items', 'source_line_id')) {
                $table->unsignedBigInteger('source_line_id')->nullable()->after('source_line_type');
            }
            if (! Schema::hasColumn('document_items', 'display_order')) {
                $table->unsignedInteger('display_order')->default(1)->after('source_line_id');
            }
            $table->index(['tenant_id', 'document_id', 'line_no'], 'document_items_line_no_idx');
            $table->index(['tenant_id', 'source_line_type', 'source_line_id'], 'document_items_source_line_idx');
        });

        Schema::table('document_versions', function (Blueprint $table): void {
            if (! Schema::hasColumn('document_versions', 'status')) {
                $table->string('status', 120)->nullable()->after('version');
            }
            if (! Schema::hasColumn('document_versions', 'snapshot_reference')) {
                $table->string('snapshot_reference')->nullable()->after('change_reason');
            }
            if (! Schema::hasColumn('document_versions', 'changed_at')) {
                $table->timestamp('changed_at')->nullable()->after('changed_by');
            }
        });

        Schema::create('document_render_logs', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('document_id')->nullable()->constrained('documents')->nullOnDelete();
            $table->unsignedBigInteger('document_template_id')->nullable();
            $table->string('render_type', 80)->default('preview');
            $table->string('status', 80)->default('rendered');
            $table->unsignedBigInteger('rendered_by')->nullable();
            $table->timestamp('rendered_at')->nullable();
            $table->text('message')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'document_id', 'created_at'], 'document_render_logs_document_idx');
            $table->index(['tenant_id', 'render_type', 'status'], 'document_render_logs_status_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('document_render_logs');
        Schema::dropIfExists('document_template_fields');
        Schema::dropIfExists('document_template_sections');
        Schema::dropIfExists('document_templates');
    }
};
