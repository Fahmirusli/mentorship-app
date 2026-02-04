<?php

namespace App\Console\Commands;

use App\Services\TelegramNotificationService;
use Illuminate\Console\Command;

class TestTelegramBot extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:test-telegram-bot {message?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test Telegram bot notification';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $message = $this->argument('message') ?? '🤖 Test notification from Mentorship System';
        
        $telegram = app(TelegramNotificationService::class);
        
        if (!$telegram->isEnabled()) {
            $this->error('❌ Telegram bot is not configured!');
            $this->info('Please set TELEGRAM_BOT_TOKEN and TELEGRAM_CHAT_ID in your .env file');
            return 1;
        }

        $this->info('📤 Sending test message...');
        
        $result = $telegram->sendMessage("🧪 <b>Test Message</b>\n\n{$message}");
        
        if ($result) {
            $this->info('✅ Message sent successfully!');
            return 0;
        } else {
            $this->error('❌ Failed to send message. Check logs for details.');
            return 1;
        }
    }
}
