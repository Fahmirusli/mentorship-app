<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class NotificationLog extends Model
{
    use HasFactory;

    protected $table = 'notifications_log';

    protected $fillable = ['user_id', 'type', 'title', 'body', 'data', 'is_read', 'read_at'];

    protected $casts = [
        'data' => 'array',
        'is_read' => 'boolean',
        'read_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Create a notification for a user.
     */
    public static function notify($userId, $type, $title, $body, $data = null)
    {
        $notification = self::create([
            'user_id' => $userId,
            'type' => $type,
            'title' => $title,
            'body' => $body,
            'data' => $data,
        ]);

        try {
            // Send FCM Push Notification if user has token
            $user = User::find($userId);
            if ($user && $user->fcm_token) {
                // Determine FCM credentials path. Use local file or environment variable
                $firebaseCredentialsPath = storage_path('app/firebase_adminsdk.json');
                
                if (file_exists($firebaseCredentialsPath)) {
                    $factory = (new \Kreait\Firebase\Factory)->withServiceAccount($firebaseCredentialsPath);
                    $messaging = $factory->createMessaging();
                    
                    $message = \Kreait\Firebase\Messaging\CloudMessage::withTarget('token', $user->fcm_token)
                        ->withNotification(\Kreait\Firebase\Messaging\Notification::create($title, $body))
                        ->withData($data ?? []);
                        
                    $messaging->send($message);
                }
            }

            // Also broadcast locally to frontend via Reverb/Pusher
            broadcast(new \App\Events\NotificationCreated($notification));
        } catch (\Exception $e) {
            \Log::error('Notification dispatch failed: ' . $e->getMessage());
        }

        return $notification;
    }
}
