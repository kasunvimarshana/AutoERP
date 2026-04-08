<?php
declare(strict_types=1);
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void {
        Schema::create('journal_entry_lines', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('tenant_id')->index();
            $table->uuid('journal_entry_id')->index();
            $table->uuid('account_id')->index();
            $table->string('type');
            $table->decimal('amount', 15, 4)->default(0);
            $table->string('currency_code', 3)->default('USD');
            $table->string('description')->nullable();
            $table->unsignedSmallInteger('sequence')->default(0);
            $table->timestamps();
            $table->foreign('journal_entry_id')->references('id')->on('journal_entries')->onDelete('cascade');
        });
    }
    public function down(): void { Schema::dropIfExists('journal_entry_lines'); }
};
