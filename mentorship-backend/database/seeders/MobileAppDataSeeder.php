<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Mentorship;
use App\Models\Appointment;
use App\Models\Schedule;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\NotificationLog;
use Carbon\Carbon;

class MobileAppDataSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('Seeding mobile app data...');

        $mentors = User::where('role', 'mentor')->get();
        $mentees = User::where('role', 'mentee')->get();

        if ($mentors->count() === 0 || $mentees->count() === 0) {
            $this->command->warn('No mentors or mentees found. Run UserSeeder first.');
            return;
        }

        // ============================================
        // 1. CREATE MENTORSHIPS
        // ============================================
        $mentorshipPairs = [
            [$mentors[0]->id, $mentees[0]->id, 'active', 'Learn React and Node.js for full stack development'],
            [$mentors[1]->id, $mentees[1]->id, 'active', 'Master machine learning with Python'],
            [$mentors[2]->id, $mentees[2]->id, 'active', 'Build and publish a Flutter mobile app'],
        ];
        if ($mentees->count() >= 4) {
            $mentorshipPairs[] = [$mentors[4]->id, $mentees[3]->id, 'active', 'Learn professional UI/UX design'];
        }
        if ($mentees->count() >= 5) {
            $mentorshipPairs[] = [$mentors[0]->id, $mentees[4]->id, 'active', 'Learn full-stack web development'];
            $mentorshipPairs[] = [$mentors[3]->id, $mentees[4]->id, 'active', 'DevOps and cloud infrastructure'];
        }

        $createdMentorships = [];
        foreach ($mentorshipPairs as $pair) {
            $mentorship = Mentorship::firstOrCreate(
                ['mentor_id' => $pair[0], 'mentee_id' => $pair[1]],
                [
                    'status' => $pair[2],
                    'goals' => $pair[3],
                    'start_date' => Carbon::now()->subMonths(rand(1, 3)),
                    'end_date' => Carbon::now()->addMonths(rand(3, 6)),
                ]
            );
            $createdMentorships[] = $mentorship;
        }
        $this->command->info('✓ ' . count($createdMentorships) . ' mentorships created/verified');

        // ============================================
        // 2. CREATE MENTOR AVAILABILITY SLOTS
        // ============================================
        // Delete old seeded slots
        Schedule::where('fee', '>', 0)->whereNotNull('date')->delete();

        $slotCount = 0;
        foreach ($mentors as $mentor) {
            $mentorProfile = $mentor->mentorProfile;
            $hourlyRate = $mentorProfile ? ($mentorProfile->hourly_rate ?? 50) : 50;

            // Create availability slots for the next 14 days
            for ($d = 0; $d < 14; $d++) {
                $date = Carbon::today()->addDays($d);
                // Skip weekends
                if ($date->isWeekend()) continue;

                // Random time slots for each day
                $timeSlots = [
                    ['09:00', '10:00'],
                    ['10:00', '11:00'],
                    ['11:00', '12:00'],
                    ['14:00', '15:00'],
                    ['15:00', '16:00'],
                    ['16:00', '17:00'],
                ];

                // Pick 3-4 random slots per day
                $selectedSlots = array_rand($timeSlots, rand(3, min(4, count($timeSlots))));
                if (!is_array($selectedSlots)) $selectedSlots = [$selectedSlots];

                foreach ($selectedSlots as $slotIdx) {
                    $slot = $timeSlots[$slotIdx];
                    Schedule::create([
                        'mentor_id' => $mentor->id,
                        'date' => $date->format('Y-m-d'),
                        'day_of_week' => $date->dayOfWeek,
                        'start_time' => $slot[0] . ':00',
                        'end_time' => $slot[1] . ':00',
                        'is_available' => true,
                        'fee' => $hourlyRate,
                        'total_slots' => 1,
                        'booked_slots' => 0,
                    ]);
                    $slotCount++;
                }
            }
        }
        $this->command->info("✓ $slotCount availability slots created for mentors");

        // ============================================
        // 3. CREATE APPOINTMENTS
        // ============================================
        // Delete old seeded appointments
        Appointment::where('notes', 'like', '%[Seeded]%')->delete();

        $appointmentCount = 0;
        foreach ($createdMentorships as $mentorship) {
            $mentor = User::find($mentorship->mentor_id);
            $mentee = User::find($mentorship->mentee_id);

            // TODAY's appointment
            Appointment::create([
                'mentorship_id' => $mentorship->id,
                'scheduled_at' => Carbon::today()->setHour(10)->setMinute(0),
                'duration_minutes' => 60,
                'status' => 'scheduled',
                'meeting_link' => 'https://meet.google.com/abc-' . strtolower(str_replace(' ', '', $mentee->name ?? 'user')),
                'notes' => '[Seeded] Discussion on project progress',
                'fee' => 50.00,
            ]);
            $appointmentCount++;

            // TOMORROW
            Appointment::create([
                'mentorship_id' => $mentorship->id,
                'scheduled_at' => Carbon::tomorrow()->setHour(14)->setMinute(0),
                'duration_minutes' => 60,
                'status' => 'scheduled',
                'meeting_link' => 'https://meet.google.com/xyz-session',
                'notes' => '[Seeded] Code review session',
                'fee' => 50.00,
            ]);
            $appointmentCount++;

            // Day after tomorrow
            Appointment::create([
                'mentorship_id' => $mentorship->id,
                'scheduled_at' => Carbon::today()->addDays(2)->setHour(11)->setMinute(0),
                'duration_minutes' => 60,
                'status' => 'scheduled',
                'meeting_link' => 'https://meet.google.com/review-session',
                'notes' => '[Seeded] Portfolio review and career planning',
                'fee' => 50.00,
            ]);
            $appointmentCount++;

            // PAST completed
            for ($i = 0; $i < 3; $i++) {
                Appointment::create([
                    'mentorship_id' => $mentorship->id,
                    'scheduled_at' => Carbon::now()->subDays(rand(2, 20))->setHour(rand(9, 16))->setMinute(0),
                    'duration_minutes' => 60,
                    'status' => 'completed',
                    'meeting_link' => 'https://meet.google.com/past-session',
                    'notes' => '[Seeded] Completed mentoring session',
                    'fee' => 50.00,
                ]);
                $appointmentCount++;
            }

            // Next week
            Appointment::create([
                'mentorship_id' => $mentorship->id,
                'scheduled_at' => Carbon::now()->addDays(rand(4, 7))->setHour(15)->setMinute(0),
                'duration_minutes' => 90,
                'status' => 'scheduled',
                'meeting_link' => 'https://meet.google.com/future-session',
                'notes' => '[Seeded] Advanced topic deep-dive',
                'fee' => 75.00,
            ]);
            $appointmentCount++;
        }
        $this->command->info("✓ $appointmentCount appointments created");

        // ============================================
        // 4. CREATE CONVERSATIONS & MESSAGES
        // ============================================
        $messageCount = 0;

        $conversationTemplates = [
            [
                ["mentee", "Hi! I'm really excited to start learning. When can we have our first session?"],
                ["mentor", "Welcome! I'm glad to have you. Let's schedule a session this week - how about Wednesday at 10am?"],
                ["mentee", "Wednesday works perfectly! Should I prepare anything beforehand?"],
                ["mentor", "Great! Please set up your development environment first. I'll send you a guide."],
                ["mentee", "Got it! I'll have everything ready by then. Thank you so much!"],
                ["mentor", "You're welcome! See you Wednesday. Don't hesitate to message if you have questions."],
                ["mentee", "Will do! Really looking forward to it 😊"],
            ],
            [
                ["mentee", "Hello! I just completed the exercise you gave me. Can you review it?"],
                ["mentor", "Sure! Send me the link to your repository and I'll take a look."],
                ["mentee", "Here it is: github.com/myproject. I tried implementing the REST API like you showed me."],
                ["mentor", "Looking good! I see you've implemented the CRUD operations. A few suggestions..."],
                ["mentor", "1. Add input validation\n2. Use middleware for auth\n3. Add error handling"],
                ["mentee", "Thanks for the detailed feedback! I'll work on those improvements."],
                ["mentor", "Perfect. Let's discuss the updates in our next session. Keep up the great work!"],
                ["mentee", "Thank you! I've already started on the validation part."],
            ],
            [
                ["mentor", "How are you progressing with the project we discussed?"],
                ["mentee", "I'm almost done with the frontend! The UI looks great now."],
                ["mentor", "Awesome! Can you share a screenshot?"],
                ["mentee", "I'll show you in our next session. I also added some animations!"],
                ["mentor", "That sounds impressive! Let me know if you need help with the backend integration."],
                ["mentee", "Actually yes, I'm having trouble connecting to the API. Can we cover that tomorrow?"],
                ["mentor", "Absolutely! I'll prepare a step-by-step guide for API integration."],
            ],
        ];

        foreach ($createdMentorships as $i => $mentorship) {
            $conversation = Conversation::findOrCreateBetween($mentorship->mentor_id, $mentorship->mentee_id);
            $mentor = User::find($mentorship->mentor_id);
            $mentee = User::find($mentorship->mentee_id);

            // Skip if conversation already has messages
            if ($conversation->messages()->count() > 0) {
                // Clear old messages to reseed
                $conversation->messages()->delete();
            }

            $template = $conversationTemplates[$i % count($conversationTemplates)];
            $baseTime = Carbon::now()->subHours(rand(1, 24));

            foreach ($template as $j => $msg) {
                $senderId = $msg[0] === 'mentor' ? $mentor->id : $mentee->id;
                $isLastTwo = $j >= count($template) - 2;

                Message::create([
                    'conversation_id' => $conversation->id,
                    'sender_id' => $senderId,
                    'body' => $msg[1],
                    'is_read' => !$isLastTwo, // Last 2 messages are unread
                    'created_at' => $baseTime->copy()->addMinutes($j * rand(3, 15)),
                    'updated_at' => $baseTime->copy()->addMinutes($j * rand(3, 15)),
                ]);
                $messageCount++;
            }

            $conversation->update(['last_message_at' => Carbon::now()->subMinutes(rand(1, 60))]);
        }
        $this->command->info("✓ $messageCount messages seeded");

        // ============================================
        // 5. CREATE NOTIFICATIONS
        // ============================================
        // Clear old notifications
        NotificationLog::where('title', 'like', '%[Seeded]%')
            ->orWhere('body', 'like', '%session with%')
            ->delete();

        $notifCount = 0;
        foreach ($createdMentorships as $mentorship) {
            $mentor = User::find($mentorship->mentor_id);
            $mentee = User::find($mentorship->mentee_id);

            // For mentee
            NotificationLog::create([
                'user_id' => $mentee->id,
                'type' => 'appointment',
                'title' => 'Session Today',
                'body' => "You have a session with {$mentor->name} today at 10:00 AM",
                'data' => ['mentor_id' => $mentor->id],
                'is_read' => false,
            ]);
            $notifCount++;

            NotificationLog::create([
                'user_id' => $mentee->id,
                'type' => 'message',
                'title' => "New message from {$mentor->name}",
                'body' => "See you at the session!",
                'data' => ['sender_id' => $mentor->id],
                'is_read' => false,
            ]);
            $notifCount++;

            // For mentor
            NotificationLog::create([
                'user_id' => $mentor->id,
                'type' => 'system',
                'title' => 'New Mentee Joined',
                'body' => "{$mentee->name} has joined your mentorship program",
                'data' => ['mentee_id' => $mentee->id],
                'is_read' => false,
            ]);
            $notifCount++;

            NotificationLog::create([
                'user_id' => $mentor->id,
                'type' => 'message',
                'title' => "New message from {$mentee->name}",
                'body' => "Really looking forward to our session!",
                'data' => ['sender_id' => $mentee->id],
                'is_read' => false,
            ]);
            $notifCount++;
        }
        $this->command->info("✓ $notifCount notifications created");
        $this->command->info('✅ Mobile app data seeding complete!');
    }
}
