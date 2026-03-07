<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tenants', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('domain')->unique();
            $table->string('plan')->default('free')->comment('free|starter|professional|enterprise');
            $table->boolean('is_active')->default(true);
            $table->json('config')->nullable()->comment('Runtime configuration: mail, payment, notifications, features, limits');
            $table->timestamps();
            $table->softDeletes();

            $table->index('domain');
            $table->index('is_active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tenants');
    }
};
