<?php
declare(strict_types=1);
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void {
        Schema::create('accounts', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('parent_id')->nullable()->constrained('accounts')->nullOnDelete();
            $table->string('code');
            $table->string('name');
            $table->string('type');
            $table->string('normal_balance')->default('debit');
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->decimal('opening_balance', 15, 4)->default(0);
            $table->decimal('current_balance', 15, 4)->default(0);
            $table->timestamps();
            $table->index('tenant_id');
            $table->unique(['tenant_id', 'code']);
        });
    }
    public function down(): void { Schema::dropIfExists('accounts'); }
};
