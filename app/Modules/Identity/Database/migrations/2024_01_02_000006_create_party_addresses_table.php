<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('party_addresses', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('party_id');
            $table->enum('type', ['billing', 'shipping', 'both']);
            $table->string('line1');
            $table->string('line2')->nullable();
            $table->string('city', 100);
            $table->string('state', 100)->nullable();
            $table->string('postal_code', 20)->nullable();
            $table->char('country_code', 2);
            $table->boolean('is_default')->default(false);
            $table->timestamps();

            $table->foreign('party_id')->references('id')->on('parties')->cascadeOnDelete();

            $table->index(['party_id', 'type']);
            $table->index(['party_id', 'is_default']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('party_addresses');
    }
};