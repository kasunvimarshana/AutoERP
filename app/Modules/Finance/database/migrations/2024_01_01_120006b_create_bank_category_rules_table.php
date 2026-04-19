<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bank_category_rules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained(null, 'id', 'bank_category_rules_tenant_id_fk')->cascadeOnDelete();
            $table->foreignId('bank_account_id')->nullable()->constrained(null, 'id', 'bank_category_rules_bank_account_id_fk')->nullOnDelete();
            $table->string('name');
            $table->unsignedInteger('priority')->default(0);
            $table->json('conditions');
            $table->foreignId('account_id')->constrained(null, 'id', 'bank_category_rules_account_id_fk');
            $table->string('description_template')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bank_category_rules');
    }
};
