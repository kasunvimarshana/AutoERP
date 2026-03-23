<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('organization_unit_attachments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('organization_unit_id');
            $table->string('uuid')->unique();
            $table->string('name');
            $table->string('file_path');
            $table->string('mime_type');
            $table->unsignedInteger('size');
            $table->string('type')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('organization_unit_id')->references('id')->on('organization_units')->onDelete('cascade');
            $table->index(['tenant_id', 'organization_unit_id', 'type']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('organization_unit_attachments');
    }
};
