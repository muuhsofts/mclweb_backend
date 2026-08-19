<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ContentBlock extends Model
{
    use HasFactory;

    protected $fillable = [
        'news_id',
        'block_order',
        'type',
        'content',
        'image_path',
        'caption',
    ];

    protected $casts = [
        'block_order' => 'integer',
    ];

    public function news()
    {
        return $this->belongsTo(News::class, 'news_id', 'news_id');
    }
}