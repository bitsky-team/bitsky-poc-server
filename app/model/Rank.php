<?php

    namespace Model;
    
    use Illuminate\Database\Eloquent\Model;

    class Rank extends Model
    {
        public $timestamps = false;
        protected $table = 'ranks';
        protected $fillable = [
            'name'
        ];
    }