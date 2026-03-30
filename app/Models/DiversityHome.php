<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DiversityHome extends Model
{
    use HasFactory;

    protected $table = 'diversity_home'; // Explicitly set table name
    protected $primaryKey = 'dhome_id'; // Explicitly set primary key

    protected $fillable = [
        'dhome_id',
        'heading',
        'description',
        'home_img',
    ];
}