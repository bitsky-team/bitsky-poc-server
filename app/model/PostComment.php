<?php

    namespace Model;
    
    use Illuminate\Database\Eloquent\Model;

    class PostComment extends Model
    {
        public $timestamps = true;
        protected $table = 'postComments';
        protected $fillable = [
            'owner_id',
            'post_id',
            'content',
            'is_foreign'
        ];
    }