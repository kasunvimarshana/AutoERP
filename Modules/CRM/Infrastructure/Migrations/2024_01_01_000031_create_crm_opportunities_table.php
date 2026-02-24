<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void
    {
        Schema::create('crm_opportunities', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('tenant_id')->index();
            $table->string('title');
            $table->uuid('lead_id')->nullable()->index();
            $table->uuid('contact_id')->nullable()->index();
            $table->uuid('account_id')->nullable()->index();
            $table->string('stage')->default('prospecting');
            $table->decimal('expected_revenue', 18, 8)->default(0);
            $table->decimal('probability', 5, 2)->default(0);
            $table->uuid('assigned_to')->nullable()->index();
            $table->date('expected_close_date')->nullable();
            $table->timestamp('won_at')->nullable();
            $table->timestamp('lost_at')->nullable();
            $table->string('lost_reason')->nullable();
            $table->string('currency', 3)->default('USD');
            $table->text('description')->nullable();
            $table->uuid('created_by')->nullable();
            $table->uuid('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }
    public function down(): void { Schema::dropIfExists('crm_opportunities'); }
};
