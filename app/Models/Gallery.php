<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Gallery extends Model
{
    protected $table = 'galleries';

    protected $fillable = [
        'title',
        'description',
        'file_path',
        'file_type',
        'thumbnail',
        'is_featured',
        'status',
        'sort_order'
    ];
}