<?php

    namespace Model;
    
    use Illuminate\Database\Eloquent\Model;

    class Example extends Model
    {
        public $timestamps = false;
        protected $table = 'example';
    }