<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'tenant_id')) {
                $table->uuid('tenant_id')->nullable()->after('id')->index();
            }
            if (! Schema::hasColumn('users', 'status')) {
                $table->string('status')->default('active')->after('email');
            }
            if (! Schema::hasColumn('users', 'avatar_path')) {
                $table->string('avatar_path')->nullable();
            }
        });
    }
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumnIfExists('tenant_id');
            $table->dropColumnIfExists('status');
            $table->dropColumnIfExists('avatar_path');
        });
    }
};
