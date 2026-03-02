<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('workflow_states', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->index();
            $table->unsignedBigInteger('workflow_definition_id');
            $table->string('name');
            $table->string('label');
            $table->boolean('is_initial')->default(false);
            $table->boolean('is_final')->default(false);
            $table->integer('sort_order')->default(0);
            $table->timestamps();

            $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');
            $table->foreign('workflow_definition_id')->references('id')->on('workflow_definitions')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('workflow_states');
    }
};
