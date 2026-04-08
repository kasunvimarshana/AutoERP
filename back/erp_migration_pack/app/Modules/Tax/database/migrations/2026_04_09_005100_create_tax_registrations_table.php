<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tax_registrations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('party_id')->constrained('parties')->cascadeOnDelete();
            $table->foreignId('country_address_id')->nullable()->constrained('addresses')->nullOnDelete();
            $table->string('tax_authority');
            $table->string('registration_number');
            $table->string('registration_type')->default("vat");
            $table->date('registered_at')->nullable();
            $table->date('expires_at')->nullable();
            $table->boolean('is_primary')->default(false);
            $table->unique(['tenant_id', 'party_id', 'registration_number']);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tax_registrations');
    }
};
