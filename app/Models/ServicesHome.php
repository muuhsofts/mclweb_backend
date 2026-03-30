<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ServicesHome extends Model
{
    use HasFactory;

    protected $table = 'services_home';
    protected $primaryKey = 'services_home_id';
    
    protected $fillable = [
        'heading',
        'description',
        'home_img',
    ];
}