<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StayConnectedHome extends Model
{
    use HasFactory;

    protected $table = 'stay_connected_home';
    protected $primaryKey = 'stay_connected_id';

    protected $fillable = [
        'description',
        'heading',
        'home_img',
    ];
}