<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Room extends Model
{
    protected $fillable = [
        'code',
        'expired_at',
    ];

    protected $casts = [
        'expired_at' => 'datetime',
    ];

    public function chatUsers()
    {
        return $this->hasMany(ChatUser::class);
    }

    public function messages()
    {
        return $this->hasMany(Message::class);
    }

    public function isExpired(): bool
    {
        return now()->greaterThan($this->expired_at);
    }
}
