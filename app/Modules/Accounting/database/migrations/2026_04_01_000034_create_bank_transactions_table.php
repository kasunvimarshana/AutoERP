<?php
declare(strict_types=1);
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void {
        Schema::create('bank_transactions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('tenant_id')->index();
            $table->uuid('bank_account_id')->index();
            $table->date('date');
            $table->string('description');
            $table->decimal('amount', 15, 4);
            $table->string('type');
            $table->string('status')->default('pending');
            $table->string('source')->default('manual');
            $table->uuid('account_id')->nullable()->index();
            $table->uuid('journal_entry_id')->nullable()->index();
            $table->string('reference')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
        });
    }
    public function down(): void { Schema::dropIfExists('bank_transactions'); }
};
