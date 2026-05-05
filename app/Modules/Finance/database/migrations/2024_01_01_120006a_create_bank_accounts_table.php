<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bank_accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained(null, 'id', 'bank_accounts_tenant_id_fk')->cascadeOnDelete();
            $table->foreignId('account_id')->constrained(null, 'id', 'bank_accounts_account_id_fk')->cascadeOnDelete();
            $table->string('name');
            $table->string('bank_name');
            $table->string('account_number');
            $table->string('routing_number')->nullable();
            $table->foreignId('currency_id')->constrained('currencies', 'id', 'bank_accounts_currency_id_fk');
            $table->decimal('current_balance', 20, 6)->default(0);
            $table->timestamp('last_sync_at')->nullable();
            $table->string('feed_provider')->nullable();
            $table->text('feed_credentials_enc')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bank_accounts');
    }
};
