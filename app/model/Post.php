<?php

    namespace Model;
    
    use Illuminate\Database\Eloquent\Model;

    class Post extends Model
    {
        public $timestamps = true;
        protected $table = 'posts';
        protected $fillable = [
            'owner_uniq_id',
            'content',
            'tag_id'
        ];
    }