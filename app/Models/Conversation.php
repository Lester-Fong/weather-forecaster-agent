<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Conversation extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'session_id',
        'ip_address',
        'user_agent',
    ];

    /**
     * Get the messages for this conversation.
     */
    public function messages()
    {
        return $this->hasMany(Message::class);
    }
}
