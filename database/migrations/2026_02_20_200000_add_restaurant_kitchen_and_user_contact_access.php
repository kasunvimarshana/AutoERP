<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ── Restaurant fields on pos_transactions ─────────────────────────
        Schema::table('pos_transactions', function (Blueprint $table) {
            $table->foreignUuid('restaurant_table_id')
                ->nullable()
                ->after('cash_register_id')
                ->constrained('restaurant_tables')
                ->nullOnDelete();
            $table->string('res_order_status', 30)
                ->nullable()
                ->after('restaurant_table_id')
                ->comment('received | cooked | served | null (non-restaurant)');
        });

        // ── User Contact Access (restrict users to specific contacts) ─────
        Schema::create('user_contact_access', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignUuid('contact_id')->constrained('contacts')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['user_id', 'contact_id']);
            $table->index('user_id');
            $table->index('contact_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_contact_access');

        Schema::table('pos_transactions', function (Blueprint $table) {
            $table->dropForeign(['restaurant_table_id']);
            $table->dropColumn(['restaurant_table_id', 'res_order_status']);
        });
    }
};
