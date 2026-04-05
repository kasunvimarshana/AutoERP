<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('pos_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('terminal_id')->constrained('pos_terminals')->cascadeOnDelete();
            $table->unsignedBigInteger('cashier_id');
            $table->string('status', 20)->default('open'); // open|closed
            $table->decimal('opening_balance', 15, 4)->default(0);
            $table->decimal('closing_balance', 15, 4)->nullable();
            $table->text('notes')->nullable();
            $table->dateTime('opened_at');
            $table->dateTime('closed_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->index(['tenant_id', 'status']);
            $table->index(['terminal_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pos_sessions');
    }
};
