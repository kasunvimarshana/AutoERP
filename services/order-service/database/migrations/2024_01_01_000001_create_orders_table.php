<?php
declare(strict_types=1);
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void {
        Schema::create('orders', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('tenant_id', 36);
            $table->string('customer_id', 36);
            $table->string('saga_id', 36)->nullable()->index();
            $table->enum('status', ['pending','confirmed','processing','shipped','delivered','cancelled'])->default('pending');
            $table->decimal('total_amount', 12, 4)->default(0);
            $table->string('currency', 3)->default('USD');
            $table->json('shipping_address')->nullable();
            $table->text('notes')->nullable();
            $table->text('cancellation_reason')->nullable();
            $table->timestamp('confirmed_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->index('tenant_id');
            $table->index('customer_id');
            $table->index('status');
        });
    }
    public function down(): void { Schema::dropIfExists('orders'); }
};
