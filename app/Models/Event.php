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
        'title',
        'slug',
        'featured_image',
        'status',
        'published_at',
        'location',
        // legacy fields (keep if needed)
        'description',
        'img_file',
        'video_link',
        'images',
        'video_links',
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
                $event->slug = Str::slug($event->title);
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