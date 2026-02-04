<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Telegram\Bot\Api;

class SetupTelegramWebhook extends Command
{
    protected $signature = 'telegram:setup-webhook';
    protected $description = 'Setup Telegram bot webhook';

    public function handle()
    {
        $telegram = new Api(config('telegram.bot_token'));
        $url = config('app.url') . '/api/telegram/webhook';

        try {
            $response = $telegram->setWebhook(['url' => $url]);
            
            if ($response) {
                $this->info("✅ Webhook set successfully to: {$url}");
                
                // Get webhook info
                $webhookInfo = $telegram->getWebhookInfo();
                $this->info("Current webhook URL: " . $webhookInfo->getUrl());
                $this->info("Pending update count: " . $webhookInfo->getPendingUpdateCount());
            } else {
                $this->error("❌ Failed to set webhook");
            }
        } catch (\Exception $e) {
            $this->error("Error: " . $e->getMessage());
        }

        return Command::SUCCESS;
    }
}
