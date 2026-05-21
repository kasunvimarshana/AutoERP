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
            $table->foreignId('organization_unit_id')->nullable()->constrained('organization_units', 'id')->nullOnDelete()->comment('Branch or department ownership');
            $table->json('metadata')->nullable()->comment('Extensible custom dynamic data');

            $table->string('commentable_type')->comment('polymorphic target type (e.g., Document, Product, Party)');
            $table->unsignedBigInteger('commentable_id')->comment('polymorphic target ID');
            $table->text('body')->comment('the actual comment content');
            $table->unsignedBigInteger('author_id')->nullable();
            $table->foreign('author_id')->references('id')->on('users')->nullOnDelete();

            $table->timestamps();

            $table->index(['tenant_id', 'commentable_type', 'commentable_id'], 'comments_type_id_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('comments');
    }
};
