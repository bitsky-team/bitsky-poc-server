<?php

    namespace Model;
    
    use Illuminate\Database\Eloquent\Model;

    class Log extends Model
    {
        public $timestamps = true;
        protected $table = 'logs';
        protected $fillable = [
            'message',
            'level'
        ];
    }