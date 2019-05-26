<?php

    namespace Model;
    
    use Illuminate\Database\Eloquent\Model;

    class Link extends Model
    {
        public $timestamps = false;
        protected $table = 'linkedDevices';
        protected $fillable = [
            'name',
            'bitsky_key',
            'active',
        ];
    }