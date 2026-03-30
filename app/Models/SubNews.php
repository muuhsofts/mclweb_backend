<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SubNews extends Model
{
    use HasFactory;

    protected $table = 'sub_news';
    protected $primaryKey = 'subnew_id';
    protected $fillable = [
        'news_id',
        'img_url',
        'heading',
        'description',
        'twitter_link',
        'facebook_link',
        'instagram_link',
        'email_url',
    ];

    public function news()
    {
        return $this->belongsTo(News::class, 'news_id', 'news_id');
    }
}