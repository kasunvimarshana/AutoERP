<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('return_authorizations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->index();
            $table->string('rma_number', 100)->unique();
            $table->string('return_type', 50);
            $table->unsignedBigInteger('party_id');
            $table->string('party_type', 50);
            $table->string('reason', 255)->nullable();
            $table->string('status', 50)->default('pending');
            $table->unsignedBigInteger('authorized_by')->nullable();
            $table->timestamp('authorized_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->unsignedBigInteger('stock_return_id')->nullable();
            $table->text('notes')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->index(['tenant_id', 'status']);
            $table->index(['tenant_id', 'party_id', 'party_type']);
            $table->index(['tenant_id', 'return_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('return_authorizations');
    }
};
