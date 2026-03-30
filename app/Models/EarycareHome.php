<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EarycareHome extends Model
{
    use HasFactory;

    protected $table = 'earycare_home';
    protected $primaryKey = 'earycare_id';

    protected $fillable = [
        'description',
        'heading',
        'home_img',
    ];
}