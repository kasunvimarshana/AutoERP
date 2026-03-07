<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->index();
            $table->unsignedBigInteger('user_id');
            $table->string('status')->default('pending');
            $table->decimal('total_amount', 12, 2)->default(0);
            $table->json('shipping_address')->nullable();
            $table->text('notes')->nullable();
            $table->json('saga_state')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->index(['tenant_id', 'status']);
            $table->index(['tenant_id', 'user_id']);
        });
    }
    public function down(): void { Schema::dropIfExists('orders'); }
};
