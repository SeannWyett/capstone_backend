<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('campus_policies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('campus_id')->unique()->constrained()->cascadeOnDelete();

            $table->boolean('guest_can_view_metadata')->default(true);
            $table->boolean('guest_can_view_file')->default(false);
            $table->boolean('guest_can_download')->default(false);

            $table->enum('student_can_view_metadata_scope', ['none', 'same_campus', 'all_campus'])->default('all_campus');
            $table->enum('student_can_view_file_scope', ['none', 'same_campus', 'all_campus'])->default('same_campus');
            $table->enum('student_can_download_scope', ['none', 'same_campus', 'all_campus'])->default('same_campus');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('campus_policies');
    }
};
