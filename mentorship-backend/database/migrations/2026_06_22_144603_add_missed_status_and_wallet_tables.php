<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up()
    {
        // 1. Add missed to appointments status ENUM
        DB::statement("ALTER TABLE appointments MODIFY COLUMN status ENUM('pending_payment', 'scheduled', 'completed', 'cancelled', 'rescheduled', 'missed') DEFAULT 'pending_payment'");

        // 2. Add wallet_balance to users table
        if (!Schema::hasColumn('users', 'wallet_balance')) {
            Schema::table('users', function (Blueprint $table) {
                $table->decimal('wallet_balance', 10, 2)->default(0)->after('role');
            });
        }

        // 3. Create wallet_transactions table
        if (!Schema::hasTable('wallet_transactions')) {
            Schema::create('wallet_transactions', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained()->onDelete('cascade');
                $table->foreignId('appointment_id')->nullable()->constrained('appointments')->onDelete('set null');
                $table->decimal('amount', 10, 2);
                $table->enum('type', ['credit', 'debit']);
                $table->string('description');
                $table->timestamps();
            });
        }
    }

    public function down()
    {
        Schema::dropIfExists('wallet_transactions');
        
        if (Schema::hasColumn('users', 'wallet_balance')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn('wallet_balance');
            });
        }

        DB::statement("ALTER TABLE appointments MODIFY COLUMN status ENUM('pending_payment', 'scheduled', 'completed', 'cancelled', 'rescheduled') DEFAULT 'pending_payment'");
    }
};
