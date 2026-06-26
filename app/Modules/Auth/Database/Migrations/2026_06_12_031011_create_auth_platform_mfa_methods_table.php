<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('auth_platform_mfa_methods', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('platform_operator_id')->unique('auth_platform_mfa_operator_uk')
                ->constrained('platform_operators', 'id')->cascadeOnDelete();
            $table->text('secret');
            $table->text('backup_code_hashes')->nullable();
            $table->string('status', 30)->default('pending');
            $table->timestamp('confirmed_at')->nullable();
            $table->timestamp('last_used_at')->nullable();
            $table->unsignedBigInteger('row_version')->default(1);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('auth_platform_mfa_methods');
    }
};
