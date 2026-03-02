<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('variation_templates', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->index();
            $table->string('name', 191);
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();

            $table->unique(['tenant_id', 'name']);
        });

        Schema::create('variation_values', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('variation_template_id');
            $table->string('value', 191);

            $table->foreign('variation_template_id')
                ->references('id')->on('variation_templates')
                ->cascadeOnDelete();

            $table->unique(['variation_template_id', 'value']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('variation_values');
        Schema::dropIfExists('variation_templates');
    }
};
