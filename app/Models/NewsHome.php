<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class NewsHome extends Model
{
    use HasFactory;

    protected $table = 'news_homes';
    protected $primaryKey = 'news_home_id';

    protected $fillable = [
        'heading',
        'description',
        'home_img',
    ];
}