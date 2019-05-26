<?php

namespace Model;

use Illuminate\Database\Eloquent\Model;

class Message extends Model
{
    public $timestamps = true;
    protected $table = 'messages';
    protected $fillable = [
        'conversation_id',
        'sender_uniq_id',
        'receiver_uniq_id',
        'content'
    ];
}