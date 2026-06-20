<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'state')) {
                $table->string('state')->nullable()->after('address');
            }
            if (!Schema::hasColumn('users', 'postcode')) {
                $table->string('postcode', 20)->nullable()->after('state');
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'postcode')) {
                $table->dropColumn('postcode');
            }
            if (Schema::hasColumn('users', 'state')) {
                $table->dropColumn('state');
            }
        });
    }
};
