<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('org_units', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->unsignedBigInteger('parent_id')->nullable();
            // type: company|division|business_unit|department|team|branch|site|other
            $table->string('type', 30)->default('department');
            $table->string('code', 50);
            $table->string('name');
            $table->text('description')->nullable();
            $table->unsignedBigInteger('manager_id')->nullable(); // user/employee
            $table->unsignedSmallInteger('level')->default(0);    // depth from root
            $table->string('path', 500)->default('/');            // materialized path "/1/5/12/"
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            // Composite unique: code must be unique within a tenant
            $table->unique(['tenant_id', 'code']);
            // Efficient sub-tree queries
            $table->index(['tenant_id', 'path']);
            $table->index(['tenant_id', 'parent_id']);
            $table->index(['tenant_id', 'type', 'is_active']);

            $table->foreign('parent_id')->references('id')->on('org_units')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('org_units');
    }
};
