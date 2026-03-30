<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SubBlog extends Model
{
    use HasFactory;

    protected $primaryKey = 'sublog_id';

    protected $fillable = [
        'heading',
        'blog_id',
        'description',
        'video_file',
        'image_file',
        'url_link',
    ];


    
     public function blog()
    {
        return $this->belongsTo(Blog::class, 'blog_id','blog_id');
    }
}