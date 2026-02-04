<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Services\TelegramNotificationService;

class SendDailyTips extends Command
{
    protected $signature = 'telegram:daily-tips';
    protected $description = 'Send daily motivational tips to mentees';

    protected $tips = [
        "💡 <b>Today's Tip:</b> Set one small achievable goal for today and celebrate when you complete it!",
        "🌟 <b>Motivation:</b> Every expert was once a beginner. Keep learning, keep growing!",
        "📚 <b>Learning Tip:</b> The best time to start was yesterday. The second best time is now!",
        "🎯 <b>Career Advice:</b> Network authentically - focus on building real relationships, not just contacts.",
        "💪 <b>Growth Mindset:</b> Mistakes are proof that you're trying. Learn from them and move forward!",
        "🚀 <b>Success Tip:</b> Consistency beats perfection. Small daily actions lead to big results.",
        "🧠 <b>Wisdom:</b> Your mentor was once where you are now. Ask questions without fear!",
        "⭐ <b>Remember:</b> Progress is progress, no matter how small. Celebrate your wins!",
        "🎨 <b>Career Growth:</b> Build projects that showcase your skills. Your portfolio speaks louder than your resume.",
        "🤝 <b>Mentorship Tip:</b> Be open to feedback. It's the fastest way to improve!",
        "📈 <b>Development:</b> Learn in public. Share your journey - it helps others and builds your brand.",
        "✨ <b>Mindset:</b> Comparison is the thief of joy. Focus on your own path and progress.",
        "🏆 <b>Achievement Unlocked:</b> You're investing in yourself by having a mentor. That's already winning!",
        "🔥 <b>Stay Focused:</b> Discipline is doing what needs to be done, even when you don't feel like it.",
        "💼 <b>Job Hunt Tip:</b> Tailor your applications. Quality applications beat quantity every time.",
    ];

    public function handle()
    {
        $telegram = app(TelegramNotificationService::class);
        
        // Get all mentees with Telegram linked
        $mentees = User::whereHas('menteeMentorships')
            ->whereNotNull('telegram_chat_id')
            ->get();

        if ($mentees->isEmpty()) {
            $this->info('No mentees with Telegram found');
            return Command::SUCCESS;
        }

        // Pick a random tip
        $tip = $this->tips[array_rand($this->tips)];

        foreach ($mentees as $mentee) {
            $telegram->sendToUser($mentee, $tip);
        }

        $this->info('Daily tips sent to ' . $mentees->count() . ' mentees');
        return Command::SUCCESS;
    }
}
