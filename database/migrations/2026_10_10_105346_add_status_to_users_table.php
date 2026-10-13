<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->enum('status', ['pending_email_verification', 'active', 'inactive'])->default('pending_email_verification')->after('role');
        });

        DB::table('users')->whereNotNull('email_verified_at')->update(['status' => 'active']);
        DB::table('users')->whereIn('role', ['campus_admin', 'super_admin'])->update(['status' => 'active']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('status');
        });
    }
};
