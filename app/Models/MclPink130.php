<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MclPink130 extends Model
{
    protected $table = 'mcl_pink130s';
    protected $primaryKey = 'mcl_id';

    protected $fillable = [
        'home_page',
        'mclPink_category',
        'description',
        'video_link',
    ];
}