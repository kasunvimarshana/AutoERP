<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateWarehousesTable extends Migration
{
    public function up()
    {
        Schema::create('warehouses', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('code')->unique();
            $table->string('name');
            $table->enum('type', ['main', 'branch', 'distribution', 'retail', 'storage']);
            $table->json('address')->nullable();
            $table->json('contact_info')->nullable();
            $table->json('operating_hours')->nullable();
            $table->decimal('latitude', 10, 8)->nullable();
            $table->decimal('longitude', 11, 8)->nullable();
            $table->boolean('is_active')->default(true);
            $table->uuid('created_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
            
            $table->index(['code', 'type', 'is_active']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('warehouses');
    }
}