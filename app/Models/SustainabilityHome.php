<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SustainabilityHome extends Model
{
    use HasFactory;

    protected $primaryKey = 'sustainability_home_id';

    protected $fillable = [
        'heading',
        'description',
        'home_img',
    ];
}