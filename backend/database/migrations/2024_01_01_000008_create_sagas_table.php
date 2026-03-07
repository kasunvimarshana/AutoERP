<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sagas', function (Blueprint $table): void {
            $table->id();
            $table->string('saga_id', 36)->unique();
            $table->string('type', 100)->index();
            $table->string('status', 30)->default('started')->index();
            $table->json('context')->nullable();
            $table->json('steps')->nullable();
            $table->text('error_message')->nullable();
            $table->unsignedBigInteger('tenant_id')->nullable()->index();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->index(['type', 'status']);
            $table->index(['tenant_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sagas');
    }
};
