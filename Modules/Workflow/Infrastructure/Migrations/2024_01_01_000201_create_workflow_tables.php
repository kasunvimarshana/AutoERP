<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('workflows', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('tenant_id')->index();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('document_type')->index();
            $table->json('states');
            $table->json('transitions')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('workflow_histories', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('tenant_id')->index();
            $table->uuid('workflow_id')->index();
            $table->string('document_type')->index();
            $table->uuid('document_id')->index();
            $table->string('from_state');
            $table->string('to_state');
            $table->uuid('actor_id')->nullable();
            $table->text('comment')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('workflow_histories');
        Schema::dropIfExists('workflows');
    }
};
