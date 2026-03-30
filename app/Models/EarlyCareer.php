<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EarlyCareer extends Model
{
    use HasFactory;

    protected $primaryKey = 'early_career_id';

    protected $fillable = [
        'category',
        'img_file',
        'video_file',
        'description',
    ];
}