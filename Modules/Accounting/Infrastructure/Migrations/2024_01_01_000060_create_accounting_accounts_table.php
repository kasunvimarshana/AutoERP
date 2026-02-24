<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('accounting_accounts', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('tenant_id')->index();
            $table->uuid('parent_id')->nullable()->index();
            $table->string('code');
            $table->string('name');
            $table->string('type');
            $table->boolean('is_active')->default(true);
            $table->decimal('balance', 18, 8)->default(0);
            $table->timestamps();
            $table->softDeletes();
            $table->unique(['tenant_id', 'code']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('accounting_accounts');
    }
};
