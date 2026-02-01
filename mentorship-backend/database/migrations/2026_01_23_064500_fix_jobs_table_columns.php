<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('jobs', function (Blueprint $table) {
            // Add missing columns if they don't exist
            if (!Schema::hasColumn('jobs', 'source')) {
                $table->string('source')->nullable();
            }
            if (!Schema::hasColumn('jobs', 'external_url')) {
                $table->string('external_url')->nullable();
            }
            if (!Schema::hasColumn('jobs', 'salary')) {
                $table->string('salary')->nullable();
            }
            if (!Schema::hasColumn('jobs', 'requirements')) {
                $table->text('requirements')->nullable();
            }
            
            // Make columns nullable
            $table->text('description')->nullable()->change();
            $table->string('company')->nullable()->change();
            $table->string('location')->nullable()->change();
        });
    }

    public function down()
    {
        Schema::table('jobs', function (Blueprint $table) {
            $table->dropColumn(['source', 'external_url', 'salary', 'requirements']);
        });
    }
};
