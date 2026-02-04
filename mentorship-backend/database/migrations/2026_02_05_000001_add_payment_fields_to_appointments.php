<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up()
    {
        // Check if columns exist before adding them
        if (!Schema::hasColumn('appointments', 'mentor_id')) {
            Schema::table('appointments', function (Blueprint $table) {
                $table->foreignId('mentor_id')->nullable()->after('mentorship_id')->constrained('users')->onDelete('cascade');
            });
        }
        
        if (!Schema::hasColumn('appointments', 'mentee_id')) {
            Schema::table('appointments', function (Blueprint $table) {
                $table->foreignId('mentee_id')->nullable()->after('mentor_id')->constrained('users')->onDelete('cascade');
            });
        }
        
        if (!Schema::hasColumn('appointments', 'fee')) {
            Schema::table('appointments', function (Blueprint $table) {
                $table->decimal('fee', 10, 2)->nullable()->after('duration_minutes');
            });
        }
        
        if (!Schema::hasColumn('appointments', 'bill_code')) {
            Schema::table('appointments', function (Blueprint $table) {
                $table->string('bill_code')->nullable()->after('fee');
            });
        }
        
        if (!Schema::hasColumn('appointments', 'payment_status')) {
            Schema::table('appointments', function (Blueprint $table) {
                $table->enum('payment_status', ['pending', 'paid', 'failed', 'refunded'])->default('pending')->after('bill_code');
            });
        }
        
        // Modify status enum using raw SQL to avoid doctrine/dbal enum issue
        DB::statement("ALTER TABLE appointments MODIFY COLUMN status ENUM('pending_payment', 'scheduled', 'completed', 'cancelled', 'rescheduled') DEFAULT 'pending_payment'");
    }

    public function down()
    {
        Schema::table('appointments', function (Blueprint $table) {
            $table->dropForeign(['mentor_id']);
            $table->dropForeign(['mentee_id']);
            $table->dropColumn(['mentor_id', 'mentee_id', 'fee', 'bill_code', 'payment_status']);
        });
        
        // Restore original status enum
        DB::statement("ALTER TABLE appointments MODIFY COLUMN status ENUM('scheduled', 'completed', 'cancelled', 'rescheduled') DEFAULT 'scheduled'");
    }
};
