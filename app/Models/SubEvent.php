<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SubEvent extends Model
{
    use HasFactory;

    protected $primaryKey = 'subevent_id';

    protected $fillable = [
        'event_id',
        'sub_category',
        'description',
        'img_file',
        'video_link',
    ];

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class, 'event_id', 'event_id');
    }
}