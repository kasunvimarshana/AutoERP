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
            $table->foreignId('platform_operator_id')->constrained('platform_operators', indexName: 'auth_platform_mfa_methods_operator_fk')->restrictOnDelete();
            $table->text('secret');
            $table->text('backup_code_hashes')->nullable();
            $table->string('status', 30);
            $table->string('enrollment_proof_digest', 64)->nullable();
            $table->timestamp('enrollment_proof_expires_at')->nullable();
            $table->unsignedBigInteger('last_totp_counter')->nullable();
            $table->timestamp('confirmed_at')->nullable();
            $table->timestamp('last_used_at')->nullable();
            $table->timestamp('disabled_at')->nullable();
            $table->unsignedBigInteger('row_version')->default(1);
            $table->timestamps();

            $table->unique('platform_operator_id', 'auth_mfa_operator_uk');
            $table->index(['status', 'enrollment_proof_expires_at'], 'auth_mfa_status_ix');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('auth_platform_mfa_methods');
    }
};
