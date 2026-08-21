<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Event extends Model
{
    use HasFactory;

    protected $table = 'events';
    protected $primaryKey = 'event_id';

    protected $fillable = [
        'event_category',
        'title',
        'slug',
        'featured_image',
        'status',
        'published_at',
        'description',      // keep for backward compatibility
        'img_file',         // keep for backward compatibility
        'video_link',       // keep for backward compatibility
        'images',           // keep for backward compatibility
        'video_links',      // keep for backward compatibility
    ];

    protected $casts = [
        'published_at' => 'datetime',
        'images' => 'array',
        'video_links' => 'array',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($event) {
            if (empty($event->slug)) {
                $event->slug = Str::slug($event->title ?? $event->event_category);
            }
        });

        static::updating(function ($event) {
            if ($event->isDirty('title') && empty($event->slug)) {
                $event->slug = Str::slug($event->title);
            }
        });
    }

    public function contentBlocks()
    {
        return $this->hasMany(EventContentBlock::class, 'event_id', 'event_id')
                    ->orderBy('block_order');
    }

    public function getFeaturedImageUrlAttribute(): ?string
    {
        return $this->featured_image ? asset($this->featured_image) : null;
    }
}