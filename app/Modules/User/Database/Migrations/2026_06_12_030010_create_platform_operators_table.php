<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const INITIAL_STATUS = 'invited';

    public function up(): void
    {
        Schema::create('platform_operators', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('row_version')->default(1);
            $table->string('first_name');
            $table->string('last_name')->nullable();
            $table->string('email')->unique('platform_operators_email_uk');
            $table->string('status', 30)->default(self::INITIAL_STATUS);
            $table->timestamp('credentials_ready_at')->nullable();
            $table->timestamp('invited_at')->nullable();
            $table->timestamp('activated_at')->nullable();
            $table->timestamp('deactivated_at')->nullable();
            $table->unsignedBigInteger('created_by_operator_id')->nullable();
            $table->unsignedBigInteger('updated_by_operator_id')->nullable();
            $table->timestamps();

            $table->index(['status', 'email'], 'platform_operators_status_email_ix');
            $table->foreign('created_by_operator_id', 'platform_operators_created_by_fk')
                ->references('id')->on('platform_operators')->restrictOnDelete();
            $table->foreign('updated_by_operator_id', 'platform_operators_updated_by_fk')
                ->references('id')->on('platform_operators')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('platform_operators');
    }
};
