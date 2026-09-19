<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('invoice_document_snapshots', function (Blueprint $table): void {
            $table->json('purchaser_reference_fields')->nullable()->after('payment_terms');
        });
    }

    public function down(): void
    {
        Schema::table('invoice_document_snapshots', function (Blueprint $table): void {
            $table->dropColumn('purchaser_reference_fields');
        });
    }
};
