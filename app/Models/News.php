<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class News extends Model
{
    use HasFactory;

    protected $primaryKey = 'news_id';

    protected $fillable = [
        'category',
        'description',
        'news_img',
        'pdf_file',
        'read_more_url_lnk',
        'images',          // JSON array of image paths
        'read_more_links', // JSON array of URLs
    ];

    protected $casts = [
        'images' => 'array',
        'read_more_links' => 'array',
    ];

    /**
     * Get the first image URL for thumbnail preview.
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