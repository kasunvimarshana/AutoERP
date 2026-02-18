<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->string('code', 50)->index();
            $table->string('name', 255);
            $table->string('type', 50)->index();
            $table->foreignId('parent_id')->nullable()->constrained('accounts')->onDelete('restrict');
            $table->foreignId('currency_id')->nullable()->constrained('currencies')->onDelete('restrict');
            $table->decimal('opening_balance', 15, 2)->default(0.00);
            $table->decimal('current_balance', 15, 2)->default(0.00);
            $table->boolean('is_active')->default(true);
            $table->text('description')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['tenant_id', 'code']);
            $table->index(['tenant_id', 'type']);
            $table->index(['tenant_id', 'parent_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('accounts');
    }
};
