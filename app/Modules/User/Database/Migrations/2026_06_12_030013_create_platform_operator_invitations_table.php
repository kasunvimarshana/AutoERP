<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const PENDING_STATUS = 'pending';

    public function up(): void
    {
        Schema::create('platform_operator_invitations', function (Blueprint $table): void {
            $table->id();
            $table->uuid('public_id')->unique('platform_operator_invitations_public_uk');
            $table->foreignId('platform_operator_id')->constrained('platform_operators', 'id')->cascadeOnDelete();
            $table->unsignedBigInteger('created_by_operator_id')->nullable();
            $table->char('token_hash', 64)->unique('platform_operator_invitations_token_uk');
            $table->text('delivery_token')->nullable();
            $table->string('status', 30)->default(self::PENDING_STATUS);
            $table->dateTime('expires_at');
            $table->timestamp('accepted_at')->nullable();
            $table->timestamp('revoked_at')->nullable();
            $table->string('revocation_reason', 500)->nullable();
            $table->unsignedBigInteger('row_version')->default(1);
            $table->timestamps();

            $table->foreign('created_by_operator_id', 'platform_operator_invitations_actor_fk')
                ->references('id')->on('platform_operators')->restrictOnDelete();
            $table->index(
                ['platform_operator_id', 'status', 'expires_at'],
                'platform_operator_invitations_operator_status_idx',
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('platform_operator_invitations');
    }
};
