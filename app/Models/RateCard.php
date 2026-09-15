<?php
// app/Models/RateCard.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RateCard extends Model
{
    use HasFactory;

    protected $table = 'rate_cards';
    protected $primaryKey = 'rate_card_id';

    protected $fillable = [
        'headline',
        'rate_card_file',
    ];

    /**
     * Get the full URL for the uploaded file.
     */
    public function getFileUrlAttribute(): ?string
    {
        if (!$this->rate_card_file) {
            return null;
        }
        return asset($this->rate_card_file);
    }

    /**
     * Append the file URL to JSON responses.
     */
    protected $appends = ['file_url'];
}