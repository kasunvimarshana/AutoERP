<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void
    {
        Schema::create('crm_leads', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('tenant_id')->index();
            $table->string('name');
            $table->string('company')->nullable();
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->string('source')->default('website');
            $table->string('status')->default('new');
            $table->decimal('score', 5, 2)->default(0);
            $table->uuid('assigned_to')->nullable()->index();
            $table->string('campaign')->nullable();
            $table->text('description')->nullable();
            $table->timestamp('converted_at')->nullable();
            $table->uuid('converted_opportunity_id')->nullable();
            $table->uuid('created_by')->nullable();
            $table->uuid('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }
    public function down(): void { Schema::dropIfExists('crm_leads'); }
};
