<?php

    namespace Model;
    
    use Illuminate\Database\Eloquent\Model;

    class Tag extends Model
    {
        public $timestamps = true;
        protected $table = 'tags';
        protected $fillable = [
            'name',
            'uses'
        ];
    }