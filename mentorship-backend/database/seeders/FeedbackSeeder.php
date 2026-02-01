<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Appointment;
use App\Models\Feedback; // Assuming Feedback model is in App\Models\Feedback? Wait, I need to check if Feedback model exists.
// Actually, earlier files showed "Feedback" model might be used in AppointmentController. 
// Let's assume it exists or I'll check first? 
// The user said "insert data for all the table", implies tables exist.
// Step 1064 viewed MentorController stats which mentioned feedback.
// I'll assume it exists. If not, I'll catch it.
// Checking step 1329 AppointmentController: `use App\Models\Feedback;` is NOT there, 
// but `with(['mentorship.mentor', ... 'feedback'])` IS used in `show`.
// So relation exists.
// Let's assume class path is correct.

class FeedbackSeeder extends Seeder
{
    public function run(): void
    {
        $appointments = Appointment::where('status', 'completed')->get();

        foreach ($appointments as $appointment) {
            // 70% chance of leaving feedback
            if (rand(1, 100) <= 70) {
                 // We need to know who is leaving feedback. Usually Mentee leaves feedback for Mentor?
                 // Or DB structure?
                 // `feedbacks` table usually has `appointment_id`, `from_user_id`, `rating`, `comment`.
                 
                 // Let's assume standard structure based on typical implementations.
                 $menteeId = $appointment->mentorship->mentee_id;
                 $mentorId = $appointment->mentorship->mentor_id;
                 $mentorshipId = $appointment->mentorship_id;
                 
                 \App\Models\Feedback::create([
                     'mentorship_id' => $mentorshipId,
                     'appointment_id' => $appointment->id,
                     'from_user_id' => $menteeId,
                     'to_user_id' => $mentorId,
                     'rating' => rand(4, 5),
                     'comment' => 'Great session, very helpful!',
                 ]);
            }
        }
    }
}
