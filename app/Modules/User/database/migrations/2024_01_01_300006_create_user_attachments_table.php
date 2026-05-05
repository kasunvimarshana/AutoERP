<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_attachments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('user_id');
            $table->string('uuid')->unique('user_attachments_uuid_uk');
            $table->string('name');
            $table->string('file_path');
            $table->string('mime_type');
            $table->unsignedInteger('size');
            $table->string('type')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('user_id', 'user_attachments_user_id_fk')->references('id')->on('users')->onDelete('cascade');
            $table->index(['tenant_id', 'user_id', 'type'], 'user_attachments_tenant_user_type_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_attachments');
    }
};
