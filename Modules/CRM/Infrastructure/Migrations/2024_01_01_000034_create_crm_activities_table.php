<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void
    {
        Schema::create('crm_activities', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('tenant_id')->index();
            $table->string('type');
            $table->string('subject');
            $table->text('description')->nullable();
            $table->string('status')->default('pending');
            $table->uuid('assigned_to')->nullable()->index();
            $table->string('related_type')->nullable()->index();
            $table->uuid('related_id')->nullable()->index();
            $table->timestamp('due_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->text('outcome')->nullable();
            $table->uuid('created_by')->nullable();
            $table->uuid('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }
    public function down(): void { Schema::dropIfExists('crm_activities'); }
};
