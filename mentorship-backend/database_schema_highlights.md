```php
Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->rememberToken();
            $table->timestamps();
        });
```

```php
Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });
```

```php
Schema::create('failed_jobs', function (Blueprint $table) {
            $table->id();
            $table->string('uuid')->unique();
            $table->text('connection');
            $table->text('queue');
            $table->longText('payload');
            $table->longText('exception');
            $table->timestamp('failed_at')->useCurrent();
        });
```

```php
Schema::create('personal_access_tokens', function (Blueprint $table) {
            $table->id();
            $table->morphs('tokenable');
            $table->string('name');
            $table->string('token', 64)->unique();
            $table->text('abilities')->nullable();
            $table->timestamp('last_used_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();
        });
```

```php
Schema::table('users', function (Blueprint $table) {
            $table->enum('role', ['admin', 'mentor', 'mentee'])->default('mentee');
            $table->string('phone')->nullable();
            $table->text('bio')->nullable();
            $table->json('skills')->nullable();
            $table->json('interests')->nullable();
            $table->string('profile_image')->nullable();
            $table->boolean('is_active')->default(true);


        });
```

```php
Schema::table('users', function (Blueprint $table) {
             $table->dropColumn(['role', 'phone', 'bio', 'skills', 'interests', 'profile_image', 'is_active']);
        });
```

```php
Schema::create('mentee_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->json('current_skills')->nullable();
            $table->json('skills_to_learn')->nullable();
            $table->text('career_goals')->nullable();
            $table->string('education_level')->nullable();
            $table->string('field_of_study')->nullable();
            $table->timestamps();
        });
```

```php
Schema::create('mentorships', function (Blueprint $table) {
            $table->id();
            $table->foreignId('mentor_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('mentee_id')->constrained('users')->onDelete('cascade');
            $table->enum('status', ['pending', 'active', 'completed', 'cancelled'])->default('pending');
            $table->text('goals')->nullable();
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->timestamps();
        });
```

```php
Schema::create('mentor_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->json('expertise_areas')->nullable();
            $table->string('industry')->nullable();
            $table->string('job_title')->nullable();
            $table->string('company')->nullable();
            $table->integer('years_of_experience')->default(0);
            $table->text('mentorship_approach')->nullable();
            $table->boolean('is_available')->default(true);
            $table->decimal('rating', 3, 2)->default(0.00);
            $table->integer('total_mentees')->default(0);
            $table->timestamps();
        });
```

```php
Schema::create('appointments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('mentorship_id')->constrained()->onDelete('cascade');
            $table->dateTime('scheduled_at');
            $table->integer('duration_minutes')->default(60);
            $table->enum('status', ['scheduled', 'completed', 'cancelled', 'rescheduled'])->default('scheduled');
            $table->text('meeting_link')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
```

```php
Schema::create('feedback', function (Blueprint $table) {
            $table->id();
            $table->foreignId('mentorship_id')->constrained()->onDelete('cascade');
            $table->foreignId('appointment_id')->nullable()->constrained()->onDelete('cascade');
            $table->foreignId('from_user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('to_user_id')->constrained('users')->onDelete('cascade');
            $table->integer('rating')->default(0);
            $table->text('comment')->nullable();
            $table->timestamps();
        });
```

```php
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
```

```php
Schema::create('schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('mentor_id')->constrained('users')->onDelete('cascade');
            $table->string('day_of_week');
            $table->time('start_time');
            $table->time('end_time');
            $table->boolean('is_available')->default(true);
            $table->timestamps();
        });
```

```php
Schema::table('users', function (Blueprint $table) {
            $table->index('role');
        });
```

```php
Schema::table('mentorships', function (Blueprint $table) {
            $table->index('status');
            $table->index('created_at');
        });
```

```php
Schema::table('jobs', function (Blueprint $table) {
            $table->index('is_active');
            // $table->index('source_platform'); // Already indexed in create_jobs_table (composite or otherwise)
            // $table->index('posted_date'); // Already indexed in create_jobs_table
        });
```

```php
Schema::table('appointments', function (Blueprint $table) {
            $table->index('status');
            $table->index('scheduled_at');
        });
```

```php
Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['role']);
        });
```

```php
Schema::table('mentorships', function (Blueprint $table) {
            $table->dropIndex(['status']);
            $table->dropIndex(['created_at']);
        });
```

```php
Schema::table('jobs', function (Blueprint $table) {
            $table->dropIndex(['is_active']);
            $table->dropIndex(['source_platform']);
            $table->dropIndex(['posted_date']);
        });
```

```php
Schema::table('appointments', function (Blueprint $table) {
            $table->dropIndex(['status']);
            $table->dropIndex(['scheduled_at']);
        });
```

```php
Schema::table('users', function (Blueprint $table) {
            $table->string('google_id')->nullable()->after('email');
            $table->string('linkedin_id')->nullable()->after('google_id');
            $table->string('avatar')->nullable()->after('linkedin_id');
        });
```

```php
Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['google_id', 'linkedin_id', 'avatar']);
        });
```

```php
Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignId('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });
```

```php
Schema::table('jobs', function (Blueprint $table) {
            //
        });
```

```php
Schema::table('jobs', function (Blueprint $table) {
            //
        });
```

```php
Schema::table('schedules', function (Blueprint $table) {
            $table->date('date')->nullable()->after('day_of_week');
            $table->integer('day_of_week')->nullable()->change();
        });
```

```php
Schema::create('resources', function (Blueprint $table) {
            $table->id();
            $table->foreignId('mentor_id')->constrained('users')->onDelete('cascade');
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('url');
            $table->string('type')->default('link'); // link, file, video
            $table->integer('downloads_count')->default(0);
            $table->timestamps();
        });
```

```php
Schema::table('appointments', function (Blueprint $table) {
            $table->decimal('fee', 10, 2)->nullable()->after('status');
        });
```

```php
Schema::table('appointments', function (Blueprint $table) {
            $table->dropColumn('fee');
        });
```

```php
Schema::table('mentor_profiles', function (Blueprint $table) {
            $table->decimal('hourly_rate', 10, 2)->default(50.00)->after('company');
        });
```

```php
Schema::table('mentor_profiles', function (Blueprint $table) {
            $table->dropColumn('hourly_rate');
        });
```

```php
Schema::table('jobs', function (Blueprint $table) {
            if (!Schema::hasColumn('jobs', 'source')) {
                $table->string('source')->nullable();
            }
        });
```

```php
Schema::table('jobs', function (Blueprint $table) {
            $table->dropColumn('source');
        });
```

```php
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
```

```php
Schema::table('jobs', function (Blueprint $table) {
            $table->dropColumn(['source', 'external_url', 'salary', 'requirements']);
        });
```

```php
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
```

```php
Schema::table('jobs', function (Blueprint $table) {
            $table->text('external_url')->change();
        });
```

```php
Schema::table('jobs', function (Blueprint $table) {
            $table->string('external_url', 255)->change();
        });
```

```php
Schema::table('appointments', function (Blueprint $table) {
            $table->string('payment_status')->default('pending')->after('status'); // pending, paid, failed
            $table->string('bill_code')->nullable()->after('payment_status');
        });
```

```php
Schema::table('appointments', function (Blueprint $table) {
            $table->dropColumn(['payment_status', 'bill_code']);
        });
```

```php
Schema::table('users', function (Blueprint $table) {
            $table->string('github_id')->nullable()->after('linkedin_id');
        });
```

```php
Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['github_id']);
        });
```

```php
Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->string('bill_code')->unique();
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('set null'); // Payer
            $table->foreignId('appointment_id')->nullable()->constrained()->onDelete('set null');
            $table->decimal('amount', 10, 2);
            $table->string('status')->default('pending'); // paid, pending, failed
            $table->string('payment_provider')->default('toyyibpay');
            $table->json('payment_metadata')->nullable(); // Store full callback
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();
        });
```

```php
Schema::table('users', function (Blueprint $table) {
            //
        });
```

```php
Schema::table('users', function (Blueprint $table) {
            //
        });
```

```php
Schema::create('favorites', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('mentor_id')->constrained('users')->onDelete('cascade');
            $table->timestamps();
            
            $table->unique(['user_id', 'mentor_id']);
        });
```

```php
Schema::table('users', function (Blueprint $table) {
            $table->string('telegram_chat_id')->nullable()->after('phone');
        });
```

```php
Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('telegram_chat_id');
        });
```

```php
Schema::table('users', function (Blueprint $table) {
            $table->boolean('is_verified')->default(false)->after('is_active');
            $table->timestamp('verified_at')->nullable()->after('is_verified');
        });
```

```php
Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['is_verified', 'verified_at']);
        });
```

```php
Schema::table('appointments', function (Blueprint $table) {
                $table->foreignId('mentor_id')->nullable()->after('mentorship_id')->constrained('users')->onDelete('cascade');
            });
```

```php
Schema::table('appointments', function (Blueprint $table) {
                $table->foreignId('mentee_id')->nullable()->after('mentor_id')->constrained('users')->onDelete('cascade');
            });
```

```php
Schema::table('appointments', function (Blueprint $table) {
                $table->decimal('fee', 10, 2)->nullable()->after('duration_minutes');
            });
```

```php
Schema::table('appointments', function (Blueprint $table) {
                $table->string('bill_code')->nullable()->after('fee');
            });
```

```php
Schema::table('appointments', function (Blueprint $table) {
                $table->enum('payment_status', ['pending', 'paid', 'failed', 'refunded'])->default('pending')->after('bill_code');
            });
```

```php
Schema::table('appointments', function (Blueprint $table) {
            $table->dropForeign(['mentor_id']);
            $table->dropForeign(['mentee_id']);
            $table->dropColumn(['mentor_id', 'mentee_id', 'fee', 'bill_code', 'payment_status']);
        });
```

```php
Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'address')) {
                $table->string('address')->nullable()->after('phone');
            }
        });
```

```php
Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'address')) {
                $table->dropColumn('address');
            }
        });
```

```php
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
```

```php
Schema::table('schedules', function (Blueprint $table) {
            $table->dropColumn(['fee', 'total_slots', 'booked_slots']);
        });
```

```php
Schema::create('conversations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_one_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('user_two_id')->constrained('users')->onDelete('cascade');
            $table->timestamp('last_message_at')->nullable();
            $table->timestamps();

            $table->unique(['user_one_id', 'user_two_id']);
            $table->index('last_message_at');
        });
```

```php
Schema::create('messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('conversation_id')->constrained()->onDelete('cascade');
            $table->foreignId('sender_id')->constrained('users')->onDelete('cascade');
            $table->text('body');
            $table->boolean('is_read')->default(false);
            $table->timestamp('read_at')->nullable();
            $table->timestamps();

            $table->index(['conversation_id', 'created_at']);
            $table->index(['sender_id', 'is_read']);
        });
```

```php
Schema::create('notifications_log', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('type'); // e.g. 'message', 'appointment', 'system'
            $table->string('title');
            $table->text('body');
            $table->json('data')->nullable(); // Extra metadata (sender_id, appointment_id, etc.)
            $table->boolean('is_read')->default(false);
            $table->timestamp('read_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'is_read']);
            $table->index(['user_id', 'created_at']);
        });
```

```php
Schema::create('job_scrape_schedules', function (Blueprint $table) {
            $table->id();
            $table->string('run_time', 5);
            $table->string('timezone')->default('Asia/Kuala_Lumpur');
            $table->string('keyword')->nullable();
            $table->boolean('enabled')->default(true);
            $table->timestamp('last_run_at')->nullable();
            $table->string('last_run_status')->nullable();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
```

```php
Schema::table('users', function (Blueprint $table) {
            $table->longText('profile_image')->nullable()->change();
            $table->longText('resume_path')->nullable()->change();
        });
```

```php
Schema::table('users', function (Blueprint $table) {
            $table->string('profile_image')->nullable()->change();
            $table->string('resume_path')->nullable()->change();
        });
```

```php
Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'state')) {
                $table->string('state')->nullable()->after('address');
            }
            if (!Schema::hasColumn('users', 'postcode')) {
                $table->string('postcode', 20)->nullable()->after('state');
            }
        });
```

```php
Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'postcode')) {
                $table->dropColumn('postcode');
            }
            if (Schema::hasColumn('users', 'state')) {
                $table->dropColumn('state');
            }
        });
```

```php
Schema::create('badges', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('description');
            $table->string('icon_url')->nullable();
            $table->integer('required_points')->default(0);
            $table->timestamps();
        });
```

```php
Schema::create('user_badges', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('badge_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
            
            $table->unique(['user_id', 'badge_id']);
        });
```

```php
Schema::table('users', function (Blueprint $table) {
            $table->integer('points')->default(0)->after('profile_image');
        });
```

```php
Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('points');
        });
```

```php
Schema::create('courses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('mentor_id')->constrained('users')->onDelete('cascade');
            $table->string('title');
            $table->text('description');
            $table->decimal('price', 8, 2);
            $table->json('tags')->nullable();
            $table->json('syllabus')->nullable();
            $table->timestamps();
        });
```

```php
Schema::create('course_enrollments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('mentee_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('course_id')->constrained('courses')->onDelete('cascade');
            $table->integer('progress_percent')->default(0);
            $table->json('completed_tasks')->nullable();
            $table->string('status')->default('active'); // active, completed
            $table->timestamps();
        });
```

```php
Schema::create('course_submissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('course_enrollment_id')->constrained('course_enrollments')->onDelete('cascade');
            $table->integer('task_index');
            $table->string('file_url')->nullable();
            $table->string('link')->nullable();
            $table->text('notes')->nullable();
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->text('mentor_feedback')->nullable();
            $table->timestamps();

            // A mentee can only have one active/approved submission per task per enrollment
            $table->unique(['course_enrollment_id', 'task_index']);
        });
```

```php
Schema::table('users', function (Blueprint $table) {
                $table->decimal('wallet_balance', 10, 2)->default(0)->after('role');
            });
```

```php
Schema::create('wallet_transactions', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained()->onDelete('cascade');
                $table->foreignId('appointment_id')->nullable()->constrained('appointments')->onDelete('set null');
                $table->decimal('amount', 10, 2);
                $table->enum('type', ['credit', 'debit']);
                $table->string('description');
                $table->timestamps();
            });
```

```php
Schema::table('users', function (Blueprint $table) {
                $table->dropColumn('wallet_balance');
            });
```

```php
Schema::create('withdrawal_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->decimal('amount', 10, 2);
            $table->string('bank_name');
            $table->string('account_number');
            $table->string('account_name');
            $table->enum('status', ['pending', 'approved', 'rejected', 'paid'])->default('pending');
            $table->text('admin_notes')->nullable();
            $table->timestamps();
        });
```

```php
Schema::table('users', function (Blueprint $table) {
            $table->string('fcm_token')->nullable()->after('password');
        });
```

```php
Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('fcm_token');
        });
```

