<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('service_bays', function (Blueprint $table) {
            $table->foreign('current_appointment_id')
                  ->references('id')
                  ->on('appointments')
                  ->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::table('service_bays', function (Blueprint $table) {
            $table->dropForeign(['current_appointment_id']);
        });
    }
};
