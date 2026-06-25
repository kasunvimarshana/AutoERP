<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('comments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('row_version')->default(1)->comment('Used for optimistic concurrency control');
            $table->foreignId('tenant_id')->constrained('tenants', 'id')->cascadeOnDelete()->comment('Multi-tenant owner reference');
            $table->foreignId('organization_unit_id')->nullable();
            $table->json('metadata')->nullable()->comment('Extensible custom dynamic data');

            $table->string('commentable_type')->comment('polymorphic target type (e.g., Document, Product, Party)');
            $table->unsignedBigInteger('commentable_id')->comment('polymorphic target ID');
            $table->string('source_module')->nullable()->comment('Generic source module key');
            $table->string('source_type')->nullable()->comment('Generic source record type');
            $table->unsignedBigInteger('source_id')->nullable()->comment('Generic source identifier');
            $table->string('source_reference')->nullable()->comment('Human-readable source number/reference');
            $table->json('source_context')->nullable()->comment('Additional source context supplied by owning module');
            $table->text('body')->comment('the actual comment content');
            $table->unsignedBigInteger('author_id')->nullable();

            $table->timestamps();

            $table->index(['tenant_id', 'commentable_type', 'commentable_id'], 'comments_type_id_idx');
            $table->index(['tenant_id', 'source_module', 'source_type', 'source_id'], 'comments_source_idx');

            $table->unique(['id', 'tenant_id'], 'comments_id_tenant_uk');
            $table->foreign(['organization_unit_id', 'tenant_id'], 'comments_organization_unit_id_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('organization_units')
                ->restrictOnDelete();
            $table->foreign(['author_id', 'tenant_id'], 'comments_author_id_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('users')
                ->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('comments');
    }
};
