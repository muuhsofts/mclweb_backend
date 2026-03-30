<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FtPink130Home extends Model
{
    use HasFactory;

    protected $table = 'ft_pink_130_home';

    protected $primaryKey = 'ft_pink_id';

    protected $fillable = [
        'heading',
        'description',
        'home_img',
    ];
}