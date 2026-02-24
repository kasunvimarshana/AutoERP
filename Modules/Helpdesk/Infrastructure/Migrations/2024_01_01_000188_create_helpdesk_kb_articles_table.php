<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('helpdesk_kb_articles', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('tenant_id')->index();
            $table->uuid('category_id')->nullable()->index();
            $table->uuid('author_id')->nullable();
            $table->string('title');
            $table->longText('body');
            $table->json('tags')->nullable();
            $table->enum('visibility', ['public', 'agents_only', 'customers_only'])->default('agents_only');
            $table->enum('status', ['draft', 'published', 'archived'])->default('draft');
            $table->unsignedInteger('helpful_count')->default(0);
            $table->unsignedInteger('not_helpful_count')->default(0);
            $table->timestamp('published_at')->nullable();
            $table->timestamp('archived_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('helpdesk_kb_articles');
    }
};
