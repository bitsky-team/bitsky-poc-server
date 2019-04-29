<?php

namespace Model;

use Illuminate\Database\Eloquent\Model;

class Conversation extends Model
{
    public $timestamps = false;
    protected $table = 'conversations';
    protected $fillable = [
        'first_user_uniq_id',
        'second_user_uniq_id',
        'link_id'
    ];
}