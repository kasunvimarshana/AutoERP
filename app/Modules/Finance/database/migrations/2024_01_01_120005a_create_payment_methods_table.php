<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_methods', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained(null, 'id', 'payment_methods_tenant_id_fk')->cascadeOnDelete();
            $table->string('name');
            $table->enum('type', ['cash', 'bank_transfer', 'card', 'cheque', 'other'])->default('bank_transfer');
            $table->foreignId('account_id')->nullable()->constrained(null, 'id', 'payment_methods_account_id_fk')->nullOnDelete();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_methods');
    }
};
