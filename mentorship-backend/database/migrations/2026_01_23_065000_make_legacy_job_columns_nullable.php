<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('jobs', function (Blueprint $table) {
            if (Schema::hasColumn('jobs', 'source_platform')) {
                $table->string('source_platform')->nullable()->change();
            }
            if (Schema::hasColumn('jobs', 'source_url')) {
                $table->string('source_url')->nullable()->change();
            }
            if (Schema::hasColumn('jobs', 'salary_range')) {
                $table->string('salary_range')->nullable()->change();
            }
            if (Schema::hasColumn('jobs', 'required_skills')) {
                $table->text('required_skills')->nullable()->change();
            }
        });
    }

    public function down()
    {
        // No need to revert specifically for this fix
    }
};
