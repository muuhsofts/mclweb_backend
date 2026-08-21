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
        'image_path',   // old single (kept null)
        'image_paths',  // JSON array
        'url',
        'caption',
    ];

    protected $casts = [
        'block_order'  => 'integer',
        'image_paths'  => 'array',
    ];

    public function news()
    {
        return $this->belongsTo(News::class, 'news_id', 'news_id');
    }

    public function getDisplayDataAttribute(): array
    {
        return [
            'content'  => $this->content,
            'url'      => $this->url,
            'images'   => $this->image_paths ?: [],
            'caption'  => $this->caption,
        ];
    }
}