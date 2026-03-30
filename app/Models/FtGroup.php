<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FtGroup extends Model
{
    use HasFactory;

    protected $primaryKey = 'ft_id';

    protected $fillable = [
        'ft_category',
        'image_file',
        'description',
        'weblink',
        'home_page'
    ];
}