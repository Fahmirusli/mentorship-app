# 4.2 Overview of the Database
The database architecture of the Uplifts ecosystem was fundamentally designed using MySQL, structured through the Laravel 12 Eloquent Object-Relational Mapping (ORM) system. The primary goal of the database was to handle a highly relational, complex, and scalable cross-platform environment that served both a Next.js web application and a Flutter mobile application. The architecture was meticulously crafted to normalize data, reduce redundancy, and ensure high query performance, which was critical for advanced features such as Natural Language Processing (NLP) resume parsing, automated web scraping, and real-time gamification tracking. All database schemas were managed using Laravel's programmatic migration files, allowing for systematic version control and consistent deployment across development and production environments.

## 4.2.1 Database Creation (Core Tables)
Instead of detailing every minor migration, this section highlights the fundamental core tables that drove the most complex functionalities of the Uplifts ecosystem.

### 1. The `users` Table (Authentication & Access Control)
The `users` table served as the central hub for the system's authentication, authorization, and role-based access control (RBAC). It was designed to comprehensively store credentials for all three distinct actor types within the system: administrators, mentors, and mentees. To support a modern, frictionless onboarding experience, the table was expanded to support OAuth 2.0 logins, accommodating external authentication providers such as Google and GitHub. Furthermore, the table stored critical functional data beyond mere authentication. It integrated fields for geolocation tracking (state, postcode, and address) to enable location-based mentor matching, stored Telegram Chat IDs for automated push notifications, and tracked the total gamification points accumulated by users. Additionally, it maintained a secure file path reference to the user's uploaded PDF resume, which was vital for the Gemini API's NLP parsing module to extract technical skills for the recommendation engine. 

```php
Schema::create('users', function (Blueprint $table) {
    $table->id();
    $table->string('name');
    $table->string('email')->unique();
    $table->timestamp('email_verified_at')->nullable();
    $table->string('password');
    $table->enum('role', ['admin', 'mentor', 'mentee'])->default('mentee');
    $table->string('google_id')->nullable();
    $table->string('github_id')->nullable();
    $table->string('resume_path')->nullable();
    $table->string('telegram_chat_id')->nullable();
    $table->integer('points')->default(0);
    $table->rememberToken();
    $table->timestamps();
});
```

### 2. The `jobs` Table (Web Scraping & Recommendation Engine)
The `jobs` table was engineered to act as the primary repository for the automated Python web scraping pipeline. Because the Uplifts ecosystem aggregated real-time job market data from multiple distinct platforms (specifically LinkedIn, JobStreet, and MauKerja), the table required a highly flexible schema capable of standardizing diverse data structures. It stored critical job attributes such as the job title, company name, location, and the precise URL of the original listing. More importantly, this table contained a `description` field mapped to a `LONGTEXT` data type. This specific field was crucial because it stored the raw, scraped text of the job requirements. During the AI recommendation process, the system's TF-IDF (Term Frequency-Inverse Document Frequency) and Cosine Similarity algorithms analyzed these descriptions against the mentee's parsed resume skills to calculate a mathematical compatibility score, outputting highly accurate, personalized job recommendations.

```php
Schema::create('jobs', function (Blueprint $table) {
    $table->id();
    $table->string('title');
    $table->string('company');
    $table->string('location')->nullable();
    $table->longText('description')->nullable();
    $table->string('source')->nullable(); // e.g., LinkedIn, MauKerja
    $table->text('external_url')->nullable();
    $table->timestamps();
});
```

### 3. The `appointments` Table (Mentorship Scheduling)
The `appointments` table was the structural backbone of the core mentor-mentee interaction within the ecosystem. It was designed to track the entire lifecycle of a mentorship session, from the initial booking request to the final completion status. The schema established foreign key constraints linking both the `mentor_id` and the `mentee_id`, ensuring strict relational integrity. To accommodate the scheduling logic, the table captured the exact date, start time, and end time of the proposed meeting, alongside an online meeting link (such as Google Meet). Furthermore, to support the premium mentorship tier, the table was expanded to include payment-related fields, specifically the `fee` and `payment_status`. This allowed the backend system to temporarily lock the appointment state in a 'pending' status until the external ToyyibPay payment gateway successfully confirmed the transaction. 

```php
Schema::create('appointments', function (Blueprint $table) {
    $table->id();
    $table->foreignId('mentor_id')->constrained('users')->onDelete('cascade');
    $table->foreignId('mentee_id')->constrained('users')->onDelete('cascade');
    $table->date('appointment_date');
    $table->time('start_time');
    $table->time('end_time');
    $table->enum('status', ['pending', 'confirmed', 'completed', 'cancelled'])->default('pending');
    $table->string('meeting_link')->nullable();
    $table->decimal('fee', 8, 2)->default(0.00);
    $table->string('payment_status')->default('unpaid');
    $table->timestamps();
});
```

### 4. The `transactions` Table (ToyyibPay Payment Gateway)
The `transactions` table was implemented exclusively to securely handle and audit financial records flowing through the ToyyibPay payment gateway. Because financial data requires a high degree of traceability and strict auditing, this table was kept separate from the core `appointments` table. It captured the exact monetary amount of the transaction, the unique reference IDs provided by the ToyyibPay API, and the ultimate status of the payment (e.g., successful, failed, or pending). By establishing a direct foreign key relationship back to the `appointments` table, the system could automatically update the appointment status once a web-hook notification was received from the payment gateway. This decoupled design prevented data corruption and ensured that any failed or disputed transactions could be independently reviewed by system administrators without disrupting the core scheduling algorithms.

```php
Schema::create('transactions', function (Blueprint $table) {
    $table->id();
    $table->foreignId('user_id')->constrained()->onDelete('cascade');
    $table->foreignId('appointment_id')->nullable()->constrained()->onDelete('set null');
    $table->string('bill_code')->nullable();
    $table->string('transaction_id')->nullable();
    $table->decimal('amount', 8, 2);
    $table->string('status')->default('pending');
    $table->string('payment_method')->nullable();
    $table->timestamps();
});
```

### 5. Gamification Tables (`badges` & `user_badges`)
To increase user engagement and retention rates within the mobile application, the database implemented a comprehensive gamification module consisting of the `badges` and `user_badges` tables. The `badges` table functioned as a static catalog, defining the various achievements available in the system, including the badge name, description, visual icon path, and the threshold of points required to unlock it. Conversely, the `user_badges` table functioned as a dynamic pivot table that recorded the many-to-many relationship between a user and their unlocked achievements. This separation allowed the system administrators to dynamically add new badges to the ecosystem without altering the core database structure. Whenever a mentee completed a course or a mentor finished an appointment, the backend logic evaluated their total points and automatically inserted a new record into the `user_badges` table if a new milestone was reached.

```php
Schema::create('badges', function (Blueprint $table) {
    $table->id();
    $table->string('name');
    $table->text('description');
    $table->string('icon_path');
    $table->integer('points_required');
    $table->timestamps();
});

Schema::create('user_badges', function (Blueprint $table) {
    $table->id();
    $table->foreignId('user_id')->constrained()->onDelete('cascade');
    $table->foreignId('badge_id')->constrained()->onDelete('cascade');
    $table->timestamp('awarded_at')->useCurrent();
    $table->timestamps();
});
```

## 4.2.2 Database Design Summary
In summary, the Uplifts ecosystem employed a highly normalized relational database architecture that successfully eliminated data redundancy and maintained strict referential integrity across 48 complex migrations. The strategic division of modules—ranging from OAuth authentication and automated job scraping to payment processing and gamification—ensured that the backend could scale efficiently. By utilizing foreign keys and cascading deletions, the database effectively managed orphaned records, ensuring that when a user was deleted, all associated appointments, transactions, and gamification points were automatically resolved. This robust structural foundation directly enabled the seamless integration of advanced Machine Learning matching algorithms, fulfilling the complex technological objectives of the research project.
