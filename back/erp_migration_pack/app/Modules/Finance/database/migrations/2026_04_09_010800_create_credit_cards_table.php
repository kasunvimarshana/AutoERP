<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('credit_cards', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('party_id')->nullable()->constrained('parties')->nullOnDelete();
            $table->foreignId('currency_id')->constrained('currencies')->cascadeOnDelete();
            $table->foreignId('gl_account_id')->constrained('chart_accounts')->cascadeOnDelete();
            $table->string('card_name');
            $table->string('card_network');
            $table->string('masked_pan');
            $table->string('holder_name')->nullable();
            $table->string('status')->default("active");
            $table->json('metadata')->nullable();
            $table->unique(['tenant_id', 'masked_pan']);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('credit_cards');
    }
};
