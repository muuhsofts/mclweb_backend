<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StayConnected extends Model
{
    use HasFactory;

    protected $primaryKey = 'stay_connected_id';
    protected $table = 'stay_connected';

    protected $fillable = [
        'category',
        'img_file',
        'description',
    ];
}