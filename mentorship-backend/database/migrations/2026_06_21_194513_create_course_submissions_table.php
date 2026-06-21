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
        Schema::create('course_submissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('course_enrollment_id')->constrained('course_enrollments')->onDelete('cascade');
            $table->integer('task_index');
            $table->string('file_url')->nullable();
            $table->string('link')->nullable();
            $table->text('notes')->nullable();
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->text('mentor_feedback')->nullable();
            $table->timestamps();

            // A mentee can only have one active/approved submission per task per enrollment
            $table->unique(['course_enrollment_id', 'task_index']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('course_submissions');
    }
};
