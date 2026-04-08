<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('party_contacts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('party_id')->constrained('parties')->cascadeOnDelete();
            $table->string('contact_type');
            $table->string('label')->nullable();
            $table->string('value');
            $table->boolean('is_primary')->default(false);
            $table->boolean('is_verified')->default(false);
            $table->index(['party_id', 'contact_type']);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('party_contacts');
    }
};
