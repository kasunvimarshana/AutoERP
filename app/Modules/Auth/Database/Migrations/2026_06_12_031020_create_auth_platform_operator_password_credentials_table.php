<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('auth_platform_operator_password_credentials', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('platform_operator_id')->constrained('platform_operators')->restrictOnDelete();
            $table->string('password_hash');
            $table->string('status', 30);
            $table->timestamp('changed_at');
            $table->timestamp('revoked_at')->nullable();
            $table->unsignedBigInteger('row_version')->default(1);
            $table->timestamps();

            $table->unique('platform_operator_id', 'auth_plat_credential_operator_uk');
            $table->index('status', 'auth_plat_credential_status_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('auth_platform_operator_password_credentials');
    }
};
