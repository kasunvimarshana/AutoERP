<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pos_sessions', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->index();
            $table->unsignedBigInteger('terminal_id');
            $table->unsignedBigInteger('cashier_id');
            $table->timestamp('opened_at');
            $table->timestamp('closed_at')->nullable();
            $table->decimal('opening_float', 20, 4);
            $table->decimal('closing_float', 20, 4)->nullable();
            $table->string('status')->default('open')->comment('open/closed');
            $table->timestamps();

            $table->foreign('terminal_id')->references('id')->on('pos_terminals')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pos_sessions');
    }
};
