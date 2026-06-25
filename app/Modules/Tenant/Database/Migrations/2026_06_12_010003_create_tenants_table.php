<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tenants', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('row_version')->default(1);
            $table->uuid('uuid')->unique('tenants_uuid_uk');
            $table->string('code', 50)->unique('tenants_code_uk');
            $table->string('name');
            $table->string('slug', 100)->unique('tenants_slug_uk');
            $table->string('logo_path')->nullable();
            $table->foreignId('base_currency_id')->nullable()->constrained('currencies', 'id')->restrictOnDelete();
            $table->enum('status', ['draft', 'active', 'inactive', 'suspended', 'archived'])->default('draft');
            $table->string('status_reason', 500)->nullable();
            $table->timestamp('activated_at')->nullable();
            $table->timestamp('suspended_at')->nullable();
            $table->timestamp('archived_at')->nullable();
            $table->unsignedBigInteger('created_by')->nullable()->index('tenants_created_by_idx');
            $table->unsignedBigInteger('updated_by')->nullable()->index('tenants_updated_by_idx');
            $table->timestamps();

            $table->index(['status', 'name'], 'tenants_status_name_idx');
            $table->unique(['id', 'base_currency_id'], 'tenants_id_currency_uk');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tenants');
    }
};
