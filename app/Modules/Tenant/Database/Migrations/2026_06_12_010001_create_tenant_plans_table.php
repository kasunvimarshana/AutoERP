<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tenant_plans', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('row_version')->default(1);
            $table->string('name');
            $table->string('slug', 100)->unique('tenant_plans_slug_uk');
            $table->boolean('is_active')->default(true);
            $table->unsignedBigInteger('created_by')->nullable()->index('tenant_plans_created_by_idx');
            $table->unsignedBigInteger('updated_by')->nullable()->index('tenant_plans_updated_by_idx');
            $table->timestamps();

            $table->index(['is_active', 'name'], 'tenant_plans_active_name_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tenant_plans');
    }
};
