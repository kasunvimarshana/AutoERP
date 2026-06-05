<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('invoice_status_histories', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('invoice_id')->constrained('invoices')->cascadeOnDelete();
            $table->string('from_status', 50)->nullable();
            $table->string('to_status', 50);
            $table->string('action', 50);
            $table->foreignId('changed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('changed_at');
            $table->string('reason_code', 100)->nullable();
            $table->text('reason')->nullable();
            $table->json('context')->nullable();
            $table->timestamps();

            $table->index(['invoice_id', 'changed_at']);
            $table->index(['invoice_id', 'to_status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invoice_status_histories');
    }
};
