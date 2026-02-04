<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up()
    {
        Schema::table('appointments', function (Blueprint $table) {
            // Add mentor_id and mentee_id for direct reference
            $table->foreignId('mentor_id')->nullable()->after('mentorship_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('mentee_id')->nullable()->after('mentor_id')->constrained('users')->onDelete('cascade');
            
            // Add payment fields
            $table->decimal('fee', 10, 2)->nullable()->after('duration_minutes');
            $table->string('bill_code')->nullable()->after('fee');
            $table->enum('payment_status', ['pending', 'paid', 'failed', 'refunded'])->default('pending')->after('bill_code');
        });
        
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
