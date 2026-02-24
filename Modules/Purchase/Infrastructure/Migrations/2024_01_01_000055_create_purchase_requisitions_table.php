<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('purchase_requisitions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('tenant_id')->index();
            $table->string('number')->index();
            $table->uuid('requested_by')->nullable()->index();
            $table->string('status')->default('draft')->index();
            $table->decimal('total_amount', 18, 8)->default(0);
            $table->string('department')->nullable();
            $table->date('required_by')->nullable();
            $table->text('notes')->nullable();
            $table->uuid('approved_by')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->uuid('rejected_by')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->timestamp('rejected_at')->nullable();
            $table->uuid('created_by')->nullable();
            $table->uuid('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('purchase_requisitions');
    }
};
