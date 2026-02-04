# Telegram Bot Setup Instructions

## Step 1: Create a Telegram Bot

1. Open Telegram and search for **@BotFather**
2. Send `/newbot` command
3. Follow the prompts:
   - Choose a name for your bot (e.g., "Uplifts Mentorship Bot")
   - Choose a username (must end with 'bot', e.g., "uplifts_mentorship_bot")
4. BotFather will give you an **API Token** - save this!

Example response:
```
Done! Congratulations on your new bot. You will find it at t.me/uplifts_mentorship_bot.
You can now add a description...

Use this token to access the HTTP API:
1234567890:ABCdefGHIjklMNOpqrsTUVwxyz1234567890

For a description of the Bot API, see this page: https://core.telegram.org/bots/api
```

## Step 2: Get Your Chat ID

### Option A: Using @userinfobot
1. Search for **@userinfobot** in Telegram
2. Start a chat with it
3. It will send your user ID (this is your CHAT_ID)

### Option B: For Group/Channel Notifications
1. Add your bot to the group/channel
2. Send any message in the group
3. Visit: `https://api.telegram.org/bot<YOUR_BOT_TOKEN>/getUpdates`
4. Look for `"chat":{"id":-1001234567890}` - that's your chat ID

## Step 3: Configure Your Server

SSH into your server and edit the .env file:

```bash
ssh root@209.97.162.99
cd /var/www/mentorship/mentorship-backend
nano .env
```

Add/update these lines:
```env
TELEGRAM_BOT_TOKEN=1234567890:ABCdefGHIjklMNOpqrsTUVwxyz1234567890
TELEGRAM_CHAT_ID=your_chat_id_here
```

Save and exit (Ctrl+X, Y, Enter)

## Step 4: Test the Bot

On the server, run:
```bash
php artisan telegram:test
```

Or with a custom message:
```bash
php artisan telegram:test "Hello from Uplifts!"
```

If successful, you should receive a message in Telegram!

## Step 5: Deploy (If Testing Locally First)

If you configured this locally first, commit and deploy:

```bash
# Locally
git add -A
git commit -m "feat: add Telegram bot notifications"
git push origin main

# On server
ssh root@209.97.162.99
cd /var/www/mentorship/mentorship-backend
git pull origin main
composer install --no-dev
systemctl restart php8.2-fpm
```

## What Gets Notified?

The system will automatically send Telegram notifications for:

- ✅ **New User Registration** - When someone signs up
- 📅 **New Appointment** - When a mentee books a session
- 🔄 **Appointment Rescheduled** - When either party changes the time
- ❌ **Appointment Cancelled** - When a session is cancelled
- ⭐ **New Feedback** - When a mentee leaves a review
- 💳 **Payment Completed** - When a transaction is successful

## Troubleshooting

### Bot not sending messages?
1. Check `.env` has correct `TELEGRAM_BOT_TOKEN` and `TELEGRAM_CHAT_ID`
2. Run `php artisan config:clear` after editing .env
3. Check logs: `tail -f storage/logs/laravel.log`
4. Verify bot token: Visit `https://api.telegram.org/bot<YOUR_BOT_TOKEN>/getMe`

### Getting "Unauthorized" error?
- Your bot token is incorrect
- Regenerate token from @BotFather if needed

### Not receiving messages?
- Wrong Chat ID
- For groups: Make sure bot is added and has permission to send messages
- Try sending a message to your bot first (start a conversation)

## Security Notes

- Never commit your bot token to Git
- Keep the `.env` file secure
- For production, consider using a dedicated notification channel
- You can create separate bots for production and development

## Custom Notifications

To send custom notifications from anywhere in your code:

```php
use App\Services\TelegramNotificationService;

$telegram = app(TelegramNotificationService::class);

// Simple message
$telegram->sendMessage('Hello from Laravel!');

// Formatted notification
$telegram->notify(
    'Custom Alert',
    'This is a custom notification with details',
    $specificChatId // optional
);
```
