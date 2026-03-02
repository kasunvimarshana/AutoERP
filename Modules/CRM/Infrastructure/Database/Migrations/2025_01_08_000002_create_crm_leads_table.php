<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('crm_leads', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->index();
            $table->unsignedBigInteger('contact_id')->nullable();
            $table->string('title', 255);
            $table->text('description')->nullable();
            $table->string('status', 30)->default('new');
            $table->decimal('estimated_value', 15, 4)->default(0);
            $table->string('currency', 3)->default('LKR');
            $table->date('expected_close_date')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['tenant_id', 'contact_id']);
            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
            $table->foreign('contact_id')->references('id')->on('crm_contacts')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('crm_leads');
    }
};
