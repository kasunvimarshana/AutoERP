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
            $table->foreignId('user_id')->unique('auth_platform_mfa_methods_user_uk')->constrained('users', 'id')->cascadeOnDelete();
            $table->text('secret');
            $table->text('backup_code_hashes')->nullable();
            $table->enum('status', ['pending', 'active', 'disabled'])->default('pending');
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
