<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FtHome extends Model
{
    use HasFactory;

    protected $table = 'ft_home';
    protected $primaryKey = 'ft_homeId';
    protected $fillable = ['heading', 'ft_home_img', 'description'];
}