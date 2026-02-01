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
        Schema::table('users', function (Blueprint $table) {
            $table->enum('role', ['admin', 'mentor', 'mentee'])->default('mentee');
            $table->string('phone')->nullable();
            $table->text('bio')->nullable();
            $table->json('skills')->nullable();
            $table->json('interests')->nullable();
            $table->string('profile_image')->nullable();
            $table->boolean('is_active')->default(true);


        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
             $table->dropColumn(['role', 'phone', 'bio', 'skills', 'interests', 'profile_image', 'is_active']);
        });
    }
};
