<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSettingsTable extends Migration
{
    public function up()
    {
        Schema::create('settings', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('key')->unique();
            $table->string('group')->nullable();
            $table->text('value');
            $table->enum('type', ['string', 'integer', 'boolean', 'json', 'array'])->default('string');
            $table->boolean('is_public')->default(false);
            $table->boolean('is_editable')->default(true);
            $table->text('description')->nullable();
            $table->json('validation_rules')->nullable();
            $table->timestamps();
            
            $table->index(['group', 'key']);
            $table->index(['is_public']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('settings');
    }
}