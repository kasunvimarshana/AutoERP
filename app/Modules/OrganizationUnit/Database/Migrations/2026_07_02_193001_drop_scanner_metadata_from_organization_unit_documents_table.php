<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('organization_unit_documents', function (Blueprint $table): void {
            $table->dropColumn('scan_engine');
            $table->dropColumn('scanned_at');
        });
    }

    public function down(): void
    {
        Schema::table('organization_unit_documents', function (Blueprint $table): void {
            $table->string('scan_engine', 100)->nullable();
            $table->dateTime('scanned_at')->nullable();
        });
    }
};
