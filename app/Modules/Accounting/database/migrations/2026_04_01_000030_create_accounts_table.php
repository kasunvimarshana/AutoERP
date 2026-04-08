<?php
declare(strict_types=1);
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void {
        Schema::create('accounts', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('tenant_id')->index();
            $table->uuid('parent_id')->nullable()->index();
            $table->string('code');
            $table->string('name');
            $table->string('type');
            $table->string('sub_type');
            $table->string('normal_balance');
            $table->string('currency_code', 3)->default('USD');
            $table->boolean('is_active')->default(true);
            $table->boolean('is_locked')->default(false);
            $table->boolean('is_system_account')->default(false);
            $table->text('description')->nullable();
            $table->string('path');
            $table->unsignedTinyInteger('level')->default(1);
            $table->timestamps();
            $table->softDeletes();
            $table->unique(['tenant_id', 'code']);
        });
    }
    public function down(): void { Schema::dropIfExists('accounts'); }
};
