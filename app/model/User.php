<?php

    namespace Model;
    
    use Illuminate\Database\Eloquent\Model;

    class User extends Model
    {
        public $timestamps = true;
        protected $table = 'users';
        protected $fillable = [
            'uniq_id',
            'email',
            'password',
            'lastname',
            'firstname',
            'token',
            'firsttime',
            'avatar',
            'biography',
            'sex',
            'birthdate',
            'relationshipstatus',
            'job',
            'birthplace',
            'livingplace',
            'rank'
        ];
    }