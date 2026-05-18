<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Conversation extends Model
{
    use HasFactory;

    protected $fillable = ['user_one_id', 'user_two_id', 'last_message_at'];

    protected $casts = [
        'last_message_at' => 'datetime',
    ];

    public function userOne()
    {
        return $this->belongsTo(User::class, 'user_one_id');
    }

    public function userTwo()
    {
        return $this->belongsTo(User::class, 'user_two_id');
    }

    public function messages()
    {
        return $this->hasMany(Message::class)->orderBy('created_at', 'asc');
    }

    public function latestMessage()
    {
        return $this->hasOne(Message::class)->latest();
    }

    /**
     * Get the other participant in a conversation.
     */
    public function getOtherUser($userId)
    {
        return $this->user_one_id == $userId ? $this->userTwo : $this->userOne;
    }

    /**
     * Get unread count for a specific user.
     */
    public function unreadCountFor($userId)
    {
        return $this->messages()
            ->where('sender_id', '!=', $userId)
            ->where('is_read', false)
            ->count();
    }

    /**
     * Find or create a conversation between two users.
     */
    public static function findOrCreateBetween($userOneId, $userTwoId)
    {
        // Always store the smaller ID first for consistency
        $first = min($userOneId, $userTwoId);
        $second = max($userOneId, $userTwoId);

        return self::firstOrCreate(
            ['user_one_id' => $first, 'user_two_id' => $second]
        );
    }
}
