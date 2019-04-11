<?php

    namespace Model;
    
    use Illuminate\Database\Eloquent\Model;

    class Notification extends Model
    {
        public $timestamps = true;
        protected $table = 'notifications';
        protected $fillable = [
            'receiver_uniq_id',
            'sender_uniq_id',
            'element_id',
            'element_type',
            'action',
            'viewed',
            'link_id'
        ];
    }