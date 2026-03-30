<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AboutMwananchi extends Model
{
    use HasFactory;

    protected $table = 'about_mwananchi'; // Explicitly set the table name

    protected $fillable = ['category', 'description', 'video_link','pdf_file'];
}
