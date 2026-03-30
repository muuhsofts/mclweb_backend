<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class About extends Model
{
    use HasFactory;

    protected $table = 'about'; // Explicitly set table name
    protected $primaryKey = 'about_id'; // Explicitly set primary key

    protected $fillable = [
        'about_id',
        'description',
        'heading',
        'home_img',
    ];
}