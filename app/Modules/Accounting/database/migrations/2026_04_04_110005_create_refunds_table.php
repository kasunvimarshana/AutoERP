<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('refunds', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->index();
            $table->unsignedBigInteger('payment_id')->index();
            $table->decimal('amount', 18, 4);
            $table->string('currency', 10)->default('USD');
            $table->string('status', 30)->default('pending'); // pending, completed, failed
            $table->text('reason')->nullable();
            $table->unsignedBigInteger('processed_by')->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->unsignedBigInteger('journal_entry_id')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('payment_id')->references('id')->on('payments')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('refunds');
    }
};
