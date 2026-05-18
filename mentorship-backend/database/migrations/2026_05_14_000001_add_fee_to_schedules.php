<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('schedules', function (Blueprint $table) {
            if (!Schema::hasColumn('schedules', 'fee')) {
                $table->decimal('fee', 8, 2)->default(50.00)->after('is_available');
            }
            if (!Schema::hasColumn('schedules', 'total_slots')) {
                $table->integer('total_slots')->default(1)->after('fee');
            }
            if (!Schema::hasColumn('schedules', 'booked_slots')) {
                $table->integer('booked_slots')->default(0)->after('total_slots');
            }
        });
    }

    public function down(): void
    {
        Schema::table('schedules', function (Blueprint $table) {
            $table->dropColumn(['fee', 'total_slots', 'booked_slots']);
        });
    }
};
