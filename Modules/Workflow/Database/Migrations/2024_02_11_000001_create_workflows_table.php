<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('workflows', function (Blueprint $table) {
            $table->id();
            $table->ulid('tenant_id');
            $table->ulid('organization_id')->nullable();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('code')->unique();
            $table->string('status', 50);
            $table->string('trigger_type', 50);
            $table->json('trigger_config')->nullable();
            $table->string('entity_type')->nullable();
            $table->unsignedBigInteger('entity_id')->nullable();
            $table->integer('version')->default(1);
            $table->boolean('is_template')->default(false);
            $table->json('metadata')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['tenant_id', 'organization_id']);
            $table->index(['tenant_id', 'status']);
            $table->index(['tenant_id', 'trigger_type']);
            $table->index(['entity_type', 'entity_id']);
            $table->index('is_template');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('workflows');
    }
};
