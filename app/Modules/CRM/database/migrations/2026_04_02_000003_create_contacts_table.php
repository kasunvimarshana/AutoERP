<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('contacts', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->unsignedBigInteger('tenant_id')->index();
            $table->string('contactable_type');
            $table->uuid('contactable_id');
            $table->string('first_name', 100);
            $table->string('last_name', 100)->nullable();
            $table->string('title', 50)->nullable();
            $table->string('department', 100)->nullable();
            $table->string('position', 100)->nullable();
            $table->string('email', 200)->nullable();
            $table->string('phone', 50)->nullable();
            $table->string('mobile', 50)->nullable();
            $table->boolean('is_primary')->default(false);
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['contactable_type', 'contactable_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contacts');
    }
};
