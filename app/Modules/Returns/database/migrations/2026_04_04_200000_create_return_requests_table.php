<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('return_requests', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->string('return_type');            // purchase_return | sales_return
            $table->unsignedBigInteger('reference_id');
            $table->string('return_number');
            $table->string('status')->default('pending');
            $table->string('reason');
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('processed_by')->nullable();
            $table->timestamp('processed_at')->nullable();
            // Extended return workflow fields
            $table->string('return_to')->default('warehouse');  // warehouse | vendor
            $table->decimal('restocking_fee', 15, 4)->default(0);
            $table->unsignedBigInteger('credit_memo_id')->nullable();
            $table->unsignedBigInteger('restocked_by')->nullable();
            $table->timestamp('restocked_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->unique(['tenant_id', 'return_number']);
            $table->index('tenant_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('return_requests');
    }
};
