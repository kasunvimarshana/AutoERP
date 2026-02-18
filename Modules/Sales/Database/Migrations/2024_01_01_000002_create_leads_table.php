<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('leads', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->string('lead_number', 50)->unique();
            $table->unsignedBigInteger('customer_id')->nullable();
            $table->string('source', 100)->nullable()->comment('Lead source: web, referral, campaign, etc.');
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('contact_name')->nullable();
            $table->string('contact_email')->nullable();
            $table->string('contact_phone', 50)->nullable();
            $table->string('company')->nullable();
            $table->enum('status', ['new', 'contacted', 'qualified', 'proposal', 'negotiation', 'won', 'lost', 'converted'])->default('new');
            $table->enum('stage', ['lead', 'prospect', 'qualified', 'customer'])->default('lead');
            $table->integer('probability')->default(0)->comment('Probability of winning (0-100)');
            $table->decimal('expected_revenue', 15, 2)->nullable();
            $table->date('expected_close_date')->nullable();
            $table->unsignedBigInteger('assigned_to')->nullable();
            $table->json('tags')->nullable();
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('tenant_id');
            $table->index('lead_number');
            $table->index('customer_id');
            $table->index('status');
            $table->index('stage');
            $table->index('assigned_to');
            $table->index(['tenant_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('leads');
    }
};
