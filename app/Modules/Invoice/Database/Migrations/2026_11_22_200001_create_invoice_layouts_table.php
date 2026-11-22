<?php

declare(strict_types=1);

/**
 * AutoERP Enterprise System
 *
 * @author Kasun Vimarshana <kasunvmail@gmail.com>
 *
 * Phone: +94713525253
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('invoice_layouts', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('row_version')->default(1);
            $table->foreignId('tenant_id')->constrained('tenants', 'id', indexName: 'invoice_layouts_tenant_fk')->restrictOnDelete();
            $table->string('code', 64);
            $table->string('name', 120);
            $table->text('description')->nullable();
            $table->string('paper_size', 32)->default('a4');
            $table->string('orientation', 16)->default('portrait');
            $table->string('view_template', 120)->default('invoice.layouts.standard');
            $table->boolean('is_system')->default(false);
            $table->boolean('is_active')->default(true);
            $table->mediumText('custom_css')->nullable();
            $table->mediumText('header_content')->nullable();
            $table->mediumText('footer_content')->nullable();
            $table->json('settings')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['tenant_id', 'code'], 'invoice_layouts_tenant_code_uk');
            $table->unique(['id', 'tenant_id'], 'invoice_layouts_id_tenant_uk');
            $table->index(['tenant_id', 'is_active'], 'invoice_layouts_tenant_active_ix');

            $table->foreign(['created_by', 'tenant_id'], 'invoice_layouts_created_by_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('users')
                ->restrictOnDelete();

            $table->foreign(['updated_by', 'tenant_id'], 'invoice_layouts_updated_by_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('users')
                ->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invoice_layouts');
    }
};
