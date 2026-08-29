<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

use function Laravel\Prompts\title;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('paper_uploads', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->enum('paper_type', ['capstone', 'thesis']);
            $table->text('abstract');
            $table->string('file_url');
            $table->string('original_filename');
            $table->unsignedBigInteger('file_size')->nullable();
            $table->integer('year');
            // $table->string('campus', 255);
            // $table->string('department', 255);
            // $table->string('course', 255);
            $table->foreignId('campus_id')->constrained('campuses')->cascadeOnDelete();
            $table->foreignId('department_id')->constrained('departments')->cascadeOnDelete();
            $table->foreignId('program_id')->constrained('programs')->cascadeOnDelete();
            // $table->foreignId('category_id')->constrained('categories')->cascadeOnDelete();
            $table->integer('views_count')->default(0);
            $table->string('researchers');
            $table->enum('viewable', ['on-site', 'available_online'])->default('on-site');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('paper_uploads');
    }
};
