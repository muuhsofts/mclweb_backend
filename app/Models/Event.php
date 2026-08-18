<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Event extends Model
{
    use HasFactory;

    protected $table = 'events';
    protected $primaryKey = 'event_id';

    protected $fillable = [
        'event_category',
        'description',
        'img_file',        // keep for backward compatibility
        'video_link',      // keep for backward compatibility
        'images',          // JSON array of image paths
        'video_links',     // JSON array of video URLs
    ];

    protected $casts = [
        'images' => 'array',
        'video_links' => 'array',
    ];

    /**
     * Get the first image URL for thumbnail.
     */
    public function getFirstImageUrlAttribute(): ?string
    {
        $images = $this->images ?? [];
        return count($images) ? asset($images[0]) : null;
    }

    /**
     * Get all image URLs as an array.
     */
    public function getImageUrlsAttribute(): array
    {
        $images = $this->images ?? [];
        return array_map(fn($path) => asset($path), $images);
    }
}