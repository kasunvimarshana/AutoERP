<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notification_templates', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->index();
            $table->string('channel', 50);
            $table->string('event_type', 100);
            $table->string('name', 255);
            $table->string('subject', 500);
            $table->text('body');
            $table->boolean('is_active')->default(true);
            $table->softDeletes();
            $table->timestamps();

            $table->unique(['tenant_id', 'channel', 'event_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notification_templates');
    }
};
