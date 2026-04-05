<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void {
        Schema::create('journal_entries', function (Blueprint $table) {
            $table->id(); $table->unsignedBigInteger('tenant_id');
            $table->string('entry_number'); $table->string('status')->default('draft');
            $table->string('description'); $table->string('currency',3)->default('USD');
            $table->decimal('total_debit',15,4)->default(0); $table->decimal('total_credit',15,4)->default(0);
            $table->string('reference')->nullable(); $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamp('posted_at')->nullable();
            $table->timestamps(); $table->softDeletes();
            $table->unique(['tenant_id','entry_number']); $table->index('tenant_id');
        });
    }
    public function down(): void { Schema::dropIfExists('journal_entries'); }
};
