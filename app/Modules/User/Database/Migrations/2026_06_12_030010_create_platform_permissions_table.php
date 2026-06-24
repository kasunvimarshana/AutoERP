<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('platform_permissions', function (Blueprint $table): void {
            $table->id();
            $table->string('name', 150)->unique('platform_permissions_name_uk');
            $table->string('description', 500);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('platform_operator_permissions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained('users', 'id')->cascadeOnDelete();
            $table->foreignId('platform_permission_id')->constrained('platform_permissions', 'id')->cascadeOnDelete();
            $table->unsignedBigInteger('granted_by')->nullable()->index('platform_operator_permissions_granted_by_idx');
            $table->timestamps();

            $table->unique(
                ['user_id', 'platform_permission_id'],
                'platform_operator_permissions_user_permission_uk',
            );
            $table->foreign('granted_by', 'platform_operator_permissions_granted_by_fk')
                ->references('id')
                ->on('users')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('platform_operator_permissions');
        Schema::dropIfExists('platform_permissions');
    }
};
