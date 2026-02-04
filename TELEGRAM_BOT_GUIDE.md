# Telegram Bot Features Guide

## 🤖 Bot Setup

Your Telegram bot is now live: **@uplifts_mentorship_bot**

### Linking Your Account

1. Go to your dashboard
2. Click "Connect Telegram" button
3. You'll get a link like: `https://t.me/uplifts_mentorship_bot?start=LINK-xxxxx`
4. Click the link or manually type `/start LINK-xxxxx` in the bot chat
5. Your account will be linked!

## 👨‍🏫 Mentor Features

### Instant Request Notifications
- When a mentee requests mentorship, you'll receive a Telegram message
- Message includes mentee name, email, and their goals
- **Interactive Buttons**: Click **[Accept]** or **[Reject]** directly in Telegram!
- No need to open the website

### Running Late?
```
/late 10
```
- Notifies your mentee you're running 10 minutes late
- Updates expected start time
- Works for sessions within next 2 hours

### View Pending Requests
```
/myrequests
```
- Shows all pending mentorship requests
- Each with Accept/Reject buttons

### View Your Sessions
```
/mysessions
```
- Lists your next 5 upcoming sessions
- Shows date, time, mentee name, duration

### Session Reminders (Automatic)
- **30 minutes before**: "Your session starts in 30 minutes"
- **5 minutes before**: "Starting soon!"
- Includes mentee name and time

### Session Feedback
- After a session, bot may ask for summary
- Type your notes directly in chat
- Saves to the session record

## 👨‍🎓 Mentee Features

### Application Status Updates
- Get notified when mentor accepts/rejects your request
- **Accepted**: "🎉 Good News! Mentor [name] accepted your request"
- **Rejected**: "😔 Request Update - try another mentor"

### Job Alerts (Daily at 2:15 AM)
- Automatically matches new jobs to your skills
- Shows top 3 matching opportunities
- Includes company, location, salary, apply link

### Search Jobs Manually
```
/jobs Laravel
/jobs Remote Developer
/jobs
```
- Search by keyword (title, company, location)
- If no keyword, matches your profile skills
- Returns top 5 jobs with apply links

### Daily Motivational Tips (9 AM)
- Receive inspirational messages every morning
- Tips about learning, growth, career development
- Keeps you motivated!

### Session Reminders
- **30 minutes before**: "Your session starts in 30 minutes - be ready!"
- **5 minutes before**: "Starting soon!"
- Includes mentor name and time

## 📚 General Commands

```
/start          - Begin using the bot (with linking code)
/help           - Show available commands
/myrequests     - View pending requests (mentors only)
/mysessions     - View upcoming sessions
/late [minutes] - Notify mentee you're running late (mentors only)
/jobs [keyword] - Search for job opportunities
```

## 🔧 Automated Features

### Session Reminders
- Runs every 2 minutes
- Checks for sessions starting in 30 min and 5 min
- Sends notifications to both mentor and mentee

### Daily Tips
- Runs at 9:00 AM (Asia/Kuala_Lumpur time)
- Sends to all mentees with Telegram linked
- Randomized motivational messages

### Job Alerts
- Runs at 2:15 AM (Asia/Kuala_Lumpur time)
- Scrapes new jobs at 2:00 AM
- Matches jobs to mentee skills and sends alerts
- Top 3 matches per mentee

### Job Scraping
- Runs at 2:00 AM daily
- Fetches latest opportunities
- Stores in database for bot access

## 💡 Tips for Best Experience

1. **Link Your Telegram**: Go to dashboard → Connect Telegram
2. **Set Your Skills**: Update profile skills for better job matching
3. **Enable Notifications**: Make sure Telegram notifications are on
4. **Use Buttons**: Click inline buttons for quick actions (Accept/Reject)
5. **Respond to Bot**: When bot asks for feedback, reply in chat

## 🛠️ Technical Details

### Webhook
- URL: `https://api.uplifts.dev/api/telegram/webhook`
- Status: Active
- Receives real-time updates from Telegram

### Scheduled Tasks
```
* * * * * cd /var/www/mentorship/mentorship-backend && php artisan schedule:run
```

### Database Fields
- `users.telegram_chat_id`: Links user to Telegram account
- Used for sending personalized notifications

### Commands List
- `telegram:session-reminders` - Every 2 minutes
- `telegram:daily-tips` - Daily at 9:00 AM
- `telegram:job-alerts` - Daily at 2:15 AM
- `telegram:setup-webhook` - Manual webhook setup

## 🐛 Troubleshooting

**Not receiving notifications?**
- Check if Telegram is linked in dashboard
- Test with admin command: POST `/api/admin/users/{id}/telegram-test`

**Commands not working?**
- Make sure you typed `/` before command
- Check if account is linked with `/start LINK-code`

**Wrong user type commands?**
- `/late` and `/myrequests` are mentor-only
- `/jobs` works for everyone
- `/mysessions` works for both mentors and mentees

## 🔐 Admin Features

Admins can:
- See Telegram status in user management (linked/not linked)
- Send test messages to users: POST `/api/admin/users/{id}/telegram-test`
- Verify users (triggers Telegram notification)

## 📊 Analytics

The bot tracks:
- Session attendance (via reminders)
- Response times (Accept/Reject buttons)
- Job alert engagement
- Command usage

---

**Bot Username**: @uplifts_mentorship_bot  
**Admin Chat ID**: 814543070  
**Webhook**: Active  
**Scheduler**: Running every minute
