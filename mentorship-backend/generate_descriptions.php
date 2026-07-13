<?php
$content = file_get_contents('all_migrations.txt');
$blocks = explode('========== ', $content);

$output = "# Database Migration Summary\n\nThis document details the database schema migrations used to build the Uplifts ecosystem, along with descriptions of their purpose.\n\n";

foreach ($blocks as $block) {
    if (trim($block) === '') continue;
    
    // Extract filename
    $lines = explode("\n", $block);
    $filenameLine = $lines[0];
    $filename = trim(str_replace(' ==========', '', $filenameLine));
    
    // Extract schema block
    if (preg_match('/Schema::(?:create|table)\(.*?\n\s*}\);/sm', $block, $matches)) {
        $code = $matches[0];
        
        // Determine description based on filename
        $desc = "This migration updates the database structure.";
        if (strpos($filename, 'create_users_table') !== false) {
            $desc = "Creates the main `users` table to store core authentication data, emails, and passwords for all actors (Admin, Mentor, Mentee).";
        } elseif (strpos($filename, 'create_mentor_profiles_table') !== false) {
            $desc = "Creates the `mentor_profiles` table to store specific details for mentors, such as their expertise, bio, and hourly rates.";
        } elseif (strpos($filename, 'create_mentee_profiles_table') !== false) {
            $desc = "Creates the `mentee_profiles` table to store mentee-specific data, including academic background and career goals.";
        } elseif (strpos($filename, 'create_mentorships_table') !== false) {
            $desc = "Establishes the `mentorships` table to track the relationship and connection status between a Mentor and a Mentee.";
        } elseif (strpos($filename, 'create_appointments_table') !== false) {
            $desc = "Creates the `appointments` table to handle scheduling and booking of mentorship sessions, including dates, times, and statuses.";
        } elseif (strpos($filename, 'create_jobs_table') !== false) {
            $desc = "Sets up the `jobs` table to store scraped job listings from platforms like LinkedIn and MauKerja for the job recommendation engine.";
        } elseif (strpos($filename, 'create_schedules_table') !== false) {
            $desc = "Creates the `schedules` table allowing mentors to define their available time slots for appointments.";
        } elseif (strpos($filename, 'create_feedback_table') !== false) {
            $desc = "Creates the `feedback` table where mentees can rate and review their mentors after a completed session.";
        } elseif (strpos($filename, 'create_transactions_table') !== false) {
            $desc = "Creates the `transactions` table to securely log all payments processed through the ToyyibPay API.";
        } elseif (strpos($filename, 'create_badges_table') !== false || strpos($filename, 'create_user_badges_table') !== false) {
            $desc = "Creates gamification tables to store available badges and track the achievements unlocked by users.";
        } elseif (strpos($filename, 'create_courses_table') !== false) {
            $desc = "Creates the `courses` table for the learning module, allowing admins or mentors to upload educational content.";
        } elseif (strpos($filename, 'create_job_scrape_schedules_table') !== false) {
            $desc = "Creates a table to manage and automate the Python web scraping schedules for job data retrieval.";
        } elseif (strpos($filename, 'add_') !== false || strpos($filename, 'update_') !== false) {
            $desc = "Alters an existing table to add new features or optimize database relationships.";
        } else {
            $desc = "Sets up necessary database tables for system functionality.";
        }

        $output .= "### " . $filename . "\n";
        $output .= $desc . "\n\n";
        $output .= "```php\n" . $code . "\n```\n\n";
        $output .= "---\n\n";
    }
}

file_put_contents('database_schema_with_descriptions.md', $output);
echo "Done!";
?>
