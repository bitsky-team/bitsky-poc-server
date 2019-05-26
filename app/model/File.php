<?php

namespace Model;

use Illuminate\Database\Eloquent\Model;

class File extends Model
{
    public $timestamps = false;
    protected $table = 'files';
    protected $fillable = [
        'path',
        'owner',
        'link_id'
    ];
}