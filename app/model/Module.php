<?php

    namespace Model;
    
    use Illuminate\Database\Eloquent\Model;

    class Module extends Model
    {
        public $timestamps = false;
        protected $table = 'modules';
        protected $fillable = [
            'state',
        ];
    }