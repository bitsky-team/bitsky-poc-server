<?php

    namespace Model;
    
    use Illuminate\Database\Eloquent\Model;

    class User extends Model
    {
        public $timestamps = false;
        protected $table = 'users';
        protected $fillable = ['uniq_id','email','password','lastname','firstname'];
    }