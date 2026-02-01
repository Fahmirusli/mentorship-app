<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        // Add indexes to frequently queried columns
        Schema::table('users', function (Blueprint $table) {
            $table->index('role');
        });

        Schema::table('mentorships', function (Blueprint $table) {
            $table->index('status');
            $table->index('created_at');
        });

        Schema::table('jobs', function (Blueprint $table) {
            $table->index('is_active');
            // $table->index('source_platform'); // Already indexed in create_jobs_table (composite or otherwise)
            // $table->index('posted_date'); // Already indexed in create_jobs_table
        });

        Schema::table('appointments', function (Blueprint $table) {
            $table->index('status');
            $table->index('scheduled_at');
        });
    }

    public function down()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['role']);
        });

        Schema::table('mentorships', function (Blueprint $table) {
            $table->dropIndex(['status']);
            $table->dropIndex(['created_at']);
        });

        Schema::table('jobs', function (Blueprint $table) {
            $table->dropIndex(['is_active']);
            $table->dropIndex(['source_platform']);
            $table->dropIndex(['posted_date']);
        });

        Schema::table('appointments', function (Blueprint $table) {
            $table->dropIndex(['status']);
            $table->dropIndex(['scheduled_at']);
        });
    }
};