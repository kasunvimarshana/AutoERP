<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('accounting_journal_entries', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('tenant_id')->index();
            $table->string('number');
            $table->string('reference')->nullable();
            $table->text('description')->nullable();
            $table->date('entry_date');
            $table->string('status')->default('draft');
            $table->uuid('created_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->unique(['tenant_id', 'number']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('accounting_journal_entries');
    }
};
