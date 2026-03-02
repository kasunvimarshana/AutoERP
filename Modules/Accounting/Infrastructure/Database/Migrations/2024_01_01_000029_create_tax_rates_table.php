<?php
declare(strict_types=1);
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void {
        Schema::create('tax_rates', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->string('name');
            $table->decimal('rate', 8, 4)->default(0);
            $table->string('type')->default('percentage');
            $table->boolean('is_active')->default(true);
            $table->boolean('is_compound')->default(false);
            $table->timestamps();
            $table->index('tenant_id');
        });
    }
    public function down(): void { Schema::dropIfExists('tax_rates'); }
};
