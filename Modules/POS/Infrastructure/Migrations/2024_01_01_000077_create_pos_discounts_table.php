<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('pos_discounts', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('tenant_id')->index();
            $table->string('code', 50);
            $table->string('name', 150);
            $table->string('type', 20)->default('percentage'); // percentage | fixed_amount
            $table->decimal('value', 18, 8)->default(0);
            $table->unsignedInteger('usage_limit')->nullable();
            $table->unsignedInteger('times_used')->default(0);
            $table->timestamp('expires_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->text('description')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->unique(['tenant_id', 'code']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pos_discounts');
    }
};
