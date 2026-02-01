<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('jobs', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('company');
            $table->text('description');
            $table->json('required_skills')->nullable();
            $table->string('location')->nullable();
            $table->string('job_type')->nullable();
            $table->string('experience_level')->nullable();
            $table->string('salary_range')->nullable();
            $table->string('source_platform');
            $table->string('source_url');
            $table->date('posted_date')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['source_platform', 'created_at']);
            $table->index('title');
            $table->index('company');
            $table->index('posted_date');
        });
    }

    public function down()
    {
        Schema::dropIfExists('jobs');
    }
};
