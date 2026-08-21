<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EventContentBlock extends Model
{
    use HasFactory;

    protected $fillable = [
        'event_id',
        'block_order',
        'type',
        'content',
        'image_paths',
        'url',
        'caption',
    ];

    protected $casts = [
        'image_paths' => 'array',
    ];

    public function event()
    {
        return $this->belongsTo(Event::class, 'event_id', 'event_id');
    }
}