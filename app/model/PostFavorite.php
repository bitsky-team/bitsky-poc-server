<?php

    namespace Model;
    
    use Illuminate\Database\Eloquent\Model;

    class PostFavorite extends Model
    {
        public $timestamps = false;
        protected $table = 'postFavorites';
        protected $fillable = [
            'post_id',
            'user_uniq_id'
        ];
    }