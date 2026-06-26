<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('platform_operator_permissions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('platform_operator_id')->constrained('platform_operators', 'id', indexName: 'platform_operator_permissions_operator_fk')->cascadeOnDelete();
            $table->foreignId('platform_permission_id')->constrained('platform_permissions', 'id', indexName: 'platform_operator_permissions_plat_permission_fk')->restrictOnDelete();
            $table->unsignedBigInteger('granted_by_operator_id')->nullable();
            $table->timestamps();

            $table->unique(
                ['platform_operator_id', 'platform_permission_id'],
                'platform_operator_permissions_operator_permission_uk',
            );
            $table->foreign('granted_by_operator_id', 'platform_operator_permissions_granted_by_fk')
                ->references('id')->on('platform_operators')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('platform_operator_permissions');
    }
};
