<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class News extends Model
{
    use HasFactory;

    protected $primaryKey = 'news_id';

    protected $fillable = [
        'title',
        'slug',
        'featured_image',
        'status',
        'published_at',
    ];

    protected $casts = [
        'published_at' => 'datetime',
    ];

    // Auto‑generate slug
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($news) {
            if (empty($news->slug)) {
                $news->slug = Str::slug($news->title);
            }
        });

        static::updating(function ($news) {
            if ($news->isDirty('title') && empty($news->slug)) {
                $news->slug = Str::slug($news->title);
            }
        });
    }

    // Relationship: ordered content blocks
    public function contentBlocks()
    {
        return $this->hasMany(ContentBlock::class, 'news_id', 'news_id')
                    ->orderBy('block_order');
    }

    // Accessor for featured image URL
    public function getFeaturedImageUrlAttribute(): ?string
    {
        return $this->featured_image ? asset($this->featured_image) : null;
    }

    // Helper to get all blocks as array (for API response)
    public function getBlocksAttribute()
    {
        return $this->contentBlocks->map(function ($block) {
            $data = [
                'id'    => $block->id,
                'type'  => $block->type,
                'order' => $block->block_order,
            ];

            switch ($block->type) {
                case 'text':
                    $data['content'] = $block->content;
                    break;
                case 'image':
                    $data['image_url'] = $block->image_path ? asset($block->image_path) : null;
                    $data['caption'] = $block->caption;
                    break;
                case 'video':
                    $data['embed'] = $block->content; // YouTube/Vimeo embed code
                    $data['caption'] = $block->caption;
                    break;
                case 'link':
                    $data['url'] = $block->content;
                    $data['title'] = $block->caption; // we store link title in caption
                    break;
            }
            return $data;
        });
    }
}