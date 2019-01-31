<?php

    namespace Model;
    
    use Illuminate\Database\Eloquent\Model;

    class PostCommentFavorite extends Model
    {
        public $timestamps = false;
        protected $table = 'postCommentFavorites';
        protected $fillable = [
            'post_comment_id',
            'user_uniq_id'
        ];
    }