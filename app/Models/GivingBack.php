<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GivingBack extends Model
{
    protected $table = 'giving_backs';
    protected $primaryKey = 'giving_id';

    protected $fillable = [
        'givingBack_category',
        'description',
        'weblink',
        'image_slider',
    ];

    protected $casts = [
        'image_slider' => 'array',
    ];
}
